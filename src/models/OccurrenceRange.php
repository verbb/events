<?php
namespace verbb\events\models;

use verbb\events\elements\Session;

use Craft;
use craft\base\Model;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\validators\DateTimeValidator;

use DateTime;

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


    // Public Methods
    // =========================================================================

    public function hasSessions(): bool
    {
        return $this->type !== self::TYPE_SINGLE;
    }

    public function getSessions(Session $startSession): array
    {
        $groupUid = $startSession->groupUid;

        if ($this->type === self::TYPE_ALL) {
            return Session::find()
                ->groupUid($groupUid)
                ->id('not ' . $startSession->id)
                ->all();
        }

        if ($this->type === self::TYPE_FUTURE) {
            return Session::find()
                ->groupUid($groupUid)
                ->id('not ' . $startSession->id)
                ->startDate('> ' . $startSession->startDate->format(DateTime::ATOM))
                ->all();
        }

        if ($this->type === self::TYPE_CUSTOM) {
            $start = $this->startDate->format(DateTime::ATOM);
            $end = $this->endDate->format(DateTime::ATOM);

            // Special-case for custom start/end dates to update, we don't update the starting session
            return Session::find()
                ->groupUid($groupUid)
                ->startDate(['and', ">= {$start}", "< {$end}"])
                ->all();
        }

        return [];
    }

    public function updateSessions(Session $startSession): void
    {
        // If no data has changed, no need to change
        if (!$this->startDateOffset && !$this->endDateOffset && !$startSession->getDirtyAttributes() && !$startSession->getDirtyFields()) {
            return;
        }

        foreach ($this->getSessions($startSession) as $session) {
            if ($this->startDateOffset) {
                $offset = ($this->startDateOffset >= 0 ? '+' : '-') . abs($this->startDateOffset) . ' seconds';
                $session->startDate->modify($offset);
            }

            if ($this->endDateOffset) {
                $offset = ($this->endDateOffset >= 0 ? '+' : '-') . abs($this->endDateOffset) . ' seconds';
                $session->endDate->modify($offset);
            }

            // Only copy across dirty attributes and fields
            foreach ($startSession->getDirtyAttributes() as $attribute) {
                $session->$attribute = $startSession->$attribute;
            }

            foreach ($startSession->getDirtyFields() as $fieldHandle) {
                $session->setFieldValue($fieldHandle, $startSession->getFieldValue($fieldHandle));
            }

            Craft::$app->getElements()->saveElement($session);
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
            'label' => Craft::t('app', 'Apply Changes To'),
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
            'label' => Craft::t('app', 'Start Date'),
            'id' => 'occurrence-range-start-date',
            'name' => 'occurrenceRange[startDate]',
            'value' => $this->startDate,
            'errors' => $this->getErrors('startDate'),
            'fieldAttributes' => [
                'data-error-key' => 'occurrenceRange.startDate',
            ],
        ]);

        $endDateField = Cp::dateFieldHtml([
            'label' => Craft::t('app', 'End Date'),
            'id' => 'occurrence-range-end-date',
            'name' => 'occurrenceRange[endDate]',
            'value' => $this->endDate,
            'errors' => $this->getErrors('endDate'),
            'fieldAttributes' => [
                'data-error-key' => 'occurrenceRange.endDate',
            ],
        ]);

        $html .= Html::beginTag('div', [
            'id' => 'apply-changes--custom',
            'class' => [
                'meta',
                'apply-changes--custom',
                ($this->type !== self::TYPE_CUSTOM ? 'hidden' : ''),
            ],
        ]) .
            $startDateField . $endDateField . 
            Html::endTag('div');

        $html .= Html::beginTag('p', ['class' => 'notice hidden has-icon occurrence-hint']) . 
            Html::tag('span', null, ['class' => 'icon', 'aria-hidden' => true]) . 
            Html::tag('span', null, ['class' => 'hint-text']) . 
        Html::endTag('div');

        return $html;
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

        return $rules;
    }

}
