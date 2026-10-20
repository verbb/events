<?php
namespace verbb\events\models;

use verbb\events\elements\Session;

use Craft;
use craft\base\Model;
use craft\db\Table;
use craft\elements\User;
use craft\errors\InvalidElementException;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\validators\DateTimeValidator;

use yii\web\ForbiddenHttpException;

use DateTime;
use Throwable;

class OccurrenceRange extends Model
{
    // Constants
    // =========================================================================

    public const TYPE_SINGLE = 'single';
    public const TYPE_ALL = 'all';
    public const TYPE_FUTURE = 'future';
    public const TYPE_CUSTOM = 'custom';


    // Static Methods
    // =========================================================================

    public static function getTypes(): array
    {
        return [
            self::TYPE_SINGLE,
            self::TYPE_ALL,
            self::TYPE_FUTURE,
            self::TYPE_CUSTOM,
        ];
    }

    public static function getTypeOptions(): array
    {
        return [
            ['label' => Craft::t('events', 'This occurrence'), 'value' => self::TYPE_SINGLE],
            ['label' => Craft::t('events', 'All occurrences'), 'value' => self::TYPE_ALL],
            ['label' => Craft::t('events', 'This and all future occurrences'), 'value' => self::TYPE_FUTURE],
            ['label' => Craft::t('events', 'Custom range'), 'value' => self::TYPE_CUSTOM],
        ];
    }


    // Properties
    // =========================================================================

    public ?string $type = self::TYPE_SINGLE;
    public ?DateTime $startDate = null;
    public ?int $startDateOffset = null;
    public ?DateTime $endDate = null;
    public ?int $endDateOffset = null;

    private bool $_requiresUserAuthorization = false;
    private ?User $_requestUser = null;
    private bool $_applied = false;


    // Public Methods
    // =========================================================================

    public function hasSessions(): bool
    {
        return $this->type !== self::TYPE_SINGLE;
    }

    public function setRequestUser(?User $user): void
    {
        $this->_requiresUserAuthorization = true;
        $this->_requestUser = $user;
    }

    public function validateForSession(Session $session): bool
    {
        if (!$this->_requiresUserAuthorization || !$this->hasSessions()) {
            return true;
        }

        if (!$this->validate()) {
            return false;
        }

        if (!$session->groupUid || !$session->getOwnerId() || $session->getPrimaryOwnerId() !== $session->getOwnerId()) {
            $this->addError('type', Craft::t('events', 'Bulk changes require an existing recurring session owned by this event.'));
            return false;
        }

        $this->_authorizeSession($session);

        return true;
    }

    public function canUpdateSessions(Session $session): bool
    {
        // A session draft must not change its published siblings before it is applied.
        return $this->hasSessions() && $session->groupUid && $session->getOwnerId() &&
            (!$session->getIsDraft() || $session->getOwner()->getIsDerivative());
    }

    public function getSessions(Session $startSession): array
    {
        if (!$this->canUpdateSessions($startSession)) {
            return [];
        }

        $query = Session::find()
            ->groupUid($startSession->groupUid)
            ->ownerId($startSession->getOwnerId())
            ->siteId($startSession->siteId)
            ->drafts(null)
            ->provisionalDrafts(null);

        if ($this->type === self::TYPE_ALL) {
            $query->id(['not', $startSession->id, $startSession->getCanonicalId()]);
        } elseif ($this->type === self::TYPE_FUTURE) {
            $query->id(['not', $startSession->id, $startSession->getCanonicalId()])
                ->startDate('> ' . $startSession->startDate->format(DateTime::ATOM));
        } elseif ($this->type === self::TYPE_CUSTOM) {
            $start = $this->startDate->format(DateTime::ATOM);
            $end = $this->endDate->format(DateTime::ATOM);

            // Custom ranges include the starting session only when its stored date is in range.
            $query->startDate(['and', ">= {$start}", "< {$end}"]);
        } else {
            return [];
        }

        // Read fresh records for each operation, bypassing SessionQuery's request-local result cache.
        return array_values(array_filter(
            iterator_to_array($query->each()),
            fn(Session $session) =>
            !$session->getIsDraft() || $session->getPrimaryOwner()->getIsDerivative()
        ));
    }

    public function updateSessions(Session $startSession): void
    {
        // Craft may save the same nested element again while removing its draft metadata.
        // A submitted range represents one operation; trusted PHP ranges remain reusable.
        if (($this->_requiresUserAuthorization && $this->_applied) || !$this->canUpdateSessions($startSession)) {
            return;
        }

        // If no data has changed, no need to change
        if (!$this->startDateOffset && !$this->endDateOffset && !$startSession->getDirtyAttributes() && !$startSession->getDirtyFields()) {
            return;
        }

        $elements = Craft::$app->getElements();
        $sessions = $this->getSessions($startSession);

        // Check every target before modifying any of them, including site permissions and authorization hooks.
        foreach ($sessions as $session) {
            $this->_authorizeSession($session);
        }

        $updates = [];

        foreach ($sessions as $original) {
            $session = clone $original;

            if ($this->startDateOffset) {
                $offset = ($this->startDateOffset >= 0 ? '+' : '-') . abs($this->startDateOffset) . ' seconds';
                $session->startDate = (clone $session->startDate)->modify($offset);
            }

            if ($this->endDateOffset) {
                $offset = ($this->endDateOffset >= 0 ? '+' : '-') . abs($this->endDateOffset) . ' seconds';
                $session->endDate = (clone $session->endDate)->modify($offset);
            }

            // Only copy across dirty attributes and fields
            foreach ($startSession->getDirtyAttributes() as $attribute) {
                if (in_array($attribute, ['id', 'uid', 'canonicalId', 'draftId', 'revisionId', 'siteId', 'ownerId', 'primaryOwnerId', 'groupUid', 'occurrenceRange', 'frequency'], true)) {
                    continue;
                }

                $session->$attribute = $startSession->$attribute;
            }

            foreach ($startSession->getDirtyFields() as $fieldHandle) {
                $session->setFieldValue($fieldHandle, $startSession->getFieldValue($fieldHandle));
            }

            $this->_authorizeSession($session);
            $updates[] = $session;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            foreach ($updates as $session) {
                $owner = $session->getOwner();

                // Event drafts initially share canonical sessions. Copy on write rather than editing published data.
                if ($session->getPrimaryOwnerId() !== $owner->id) {
                    $sharedId = $session->id;
                    $session = $elements->duplicateElement($session, [
                        'canonicalId' => $owner->getIsDerivative() ? $session->getCanonicalId() : null,
                        'primaryOwner' => $owner,
                        'owner' => $owner,
                        'occurrenceRange' => null,
                    ]);
                    Db::delete(Table::ELEMENTS_OWNERS, ['elementId' => $sharedId, 'ownerId' => $owner->id]);
                } elseif (!$elements->saveElement($session)) {
                    throw new InvalidElementException($session);
                }
            }

            $transaction->commit();
            $this->_applied = true;
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();
        $labels['startDate'] = Craft::t('events', 'Apply Changes Start Date');
        $labels['endDate'] = Craft::t('events', 'Apply Changes End Date');

        return $labels;
    }

    public function getInputHtml(Session $session): string
    {
        $html = '';

        $applyField = Cp::renderTemplate('_includes/forms/radioGroup.twig', [
            'label' => Craft::t('events', 'Apply Changes To'),
            'id' => 'occurrence-range-type',
            'name' => 'occurrenceRange[type]',
            'value' => $this->type,
            'options' => self::getTypeOptions(),
            'toggle' => true,
            'targetPrefix' => '.apply-changes--',
            'errors' => $this->getErrors('type'),
            'fieldAttributes' => [
                'data-error-key' => 'occurrenceRange.type',
            ],
        ]);

        $html .= Html::hiddenInput('occurrenceRange[startDateOffset]', $this->startDateOffset, [
            'id' => 'occurrence-range-start-date-offset',
        ]);

        $html .= Html::hiddenInput('occurrenceRange[endDateOffset]', $this->endDateOffset, [
            'id' => 'occurrence-range-end-date-offset',
        ]);

        $html .= Html::beginTag('div', ['class' => 'field occurrence-range-field']) .
            Html::tag('legend', Craft::t('events', 'Apply changes to'), ['class' => 'h6']) .
            Html::tag('div', $applyField) .
            Html::endTag('div');

        $startDateField = Cp::dateFieldHtml([
            'label' => Craft::t('events', 'Start Date'),
            'id' => 'occurrence-range-start-date',
            'name' => 'occurrenceRange[startDate]',
            'value' => $this->startDate,
            'errors' => $this->getErrors('startDate'),
            'fieldAttributes' => [
                'data-error-key' => 'occurrenceRange.startDate',
            ],
        ]);

        $endDateField = Cp::dateFieldHtml([
            'label' => Craft::t('events', 'End Date'),
            'id' => 'occurrence-range-end-date',
            'name' => 'occurrenceRange[endDate]',
            'value' => $this->endDate,
            'errors' => $this->getErrors('endDate'),
            'fieldAttributes' => [
                'data-error-key' => 'occurrenceRange.endDate',
            ],
        ]);

        $html .= Html::tag('div', $startDateField . $endDateField, [
            'id' => 'apply-changes--custom',
            'class' => [
                'meta',
                'apply-changes--custom',
                ($this->type !== self::TYPE_CUSTOM ? 'hidden' : ''),
            ],
        ]);

        $html .= Html::beginTag('p', ['class' => 'notice hidden has-icon occurrence-hint']) .
            Html::tag('span', null, ['class' => 'icon', 'aria-hidden' => true]) .
            Html::tag('span', null, ['class' => 'hint-text']) .
        Html::endTag('p');

        return $html;
    }


    // Private Methods
    // =========================================================================

    private function _authorizeSession(Session $session): void
    {
        if ($this->_requiresUserAuthorization && (!$this->_requestUser || !Craft::$app->getElements()->canSave($session, $this->_requestUser))) {
            throw new ForbiddenHttpException('User not authorized to update every session in this occurrence range.');
        }
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['type'], 'required'];
        $rules[] = [['type'], 'in', 'range' => self::getTypes()];

        $rules[] = [['startDate', 'endDate'], 'required', 'when' => fn() => $this->type === self::TYPE_CUSTOM];

        $rules[] = [['startDate', 'endDate'], DateTimeValidator::class];
        $rules[] = [['startDateOffset', 'endDateOffset'], 'integer'];

        return $rules;
    }

}
