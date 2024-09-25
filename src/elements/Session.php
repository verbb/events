<?php
namespace verbb\events\elements;

use verbb\events\Events;
use verbb\events\assetbundles\SessionIndexAsset;
use verbb\events\assetbundles\SessionEditAsset;
use verbb\events\base\FrequencyInterface;
use verbb\events\elements\actions\DeleteSessions;
use verbb\events\elements\db\SessionQuery;
use verbb\events\elements\traits\PurchasedTicketTrait;
use verbb\events\frequencies;
use verbb\events\models\OccurrenceRange;
use verbb\events\models\SessionRecurrenceData;
use verbb\events\records\Session as SessionRecord;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use craft\base\NestedElementTrait;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\Restore;
use craft\elements\User;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\i18n\Formatter;
use craft\i18n\Locale;
use craft\models\FieldLayout;
use craft\validators\DateTimeValidator;

use yii\base\Exception;

use verbb\base\helpers\Locale as LocaleHelper;

use DateTime;

class Session extends Element implements NestedElementInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('events', 'Session');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('events', 'Sessions');
    }

    public static function refHandle(): ?string
    {
        return 'session';
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function trackChanges(): bool
    {
        return true;
    }

    public static function find(): SessionQuery
    {
        return new SessionQuery(static::class);
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return $context->handle . '_Session';
    }

    public static function gqlScopesByContext(mixed $context): array
    {
        return ['eventsEventTypes.' . $context->uid];
    }

    protected static function defineFieldLayouts(?string $source): array
    {
        // Being attached to an event element means we always have context, so improve performance
        // by not loading in all field layouts for this element type.
        return [];
    }

    protected static function defineSources(string $context = null): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('events', 'All sessions'),
                'defaultSort' => ['startDate', 'desc'],
            ],
        ];
    }

    protected static function defineActions(string $source): array
    {
        $actions = [];
        $actions[] = DeleteSessions::class;
        $actions[] = Restore::class;

        return $actions;
    }

    protected static function includeSetStatusAction(): bool
    {
        return true;
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'startDate' => ['label' => Craft::t('events', 'Start Date')],
            'endDate' => ['label' => Craft::t('events', 'End Date')],
            'allDay' => ['label' => Craft::t('events', 'All Day')],
            'recurring' => ['label' => Craft::t('app', 'Is Occurrence?')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];
        $attributes[] = 'startDate';
        $attributes[] = 'endDate';
        $attributes[] = 'allDay';
        $attributes[] = 'recurring';

        return $attributes;
    }


    // Traits
    // =========================================================================

    use PurchasedTicketTrait;

    use NestedElementTrait {
        eagerLoadingMap as traitEagerLoadingMap;
        setPrimaryOwner as traitSetPrimaryOwner;
        setOwner as traitSetOwner;
        setEagerLoadedElements as traitSetEagerLoadedElements;
    }


    // Properties
    // =========================================================================

    public ?DateTime $startDate = null;
    public ?DateTime $endDate = null;
    public bool $allDay = false;
    public ?string $groupUid = null;
    public ?int $sortOrder = null;
    public bool $deletedWithEvent = false;

    public FrequencyInterface $frequency;
    public ?OccurrenceRange $occurrenceRange = null;

    private ?string $_eventSlug = null;
    private ?string $_eventTypeHandle = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Ensure that `frequencyData` is always typecast properly
        if (!array_key_exists('frequency', $config) || !($config['frequency'] instanceof FrequencyInterface)) {
            $config['frequency'] = new frequencies\Once();
        }

        parent::__construct($config);
    }

    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        $event = $this->getOwner();

        if ($event === null) {
            return false;
        }

        return $event->canView($user);
    }

    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }

        $event = $this->getOwner();

        if ($event === null) {
            return false;
        }

        return $event->canSave($user);
    }

    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        return $this->canSave($user);
    }

    public function canDuplicate(User $user): bool
    {
        if (parent::canDuplicate($user)) {
            return true;
        }

        return $this->canSave($user);
    }

    public function setAttributesFromRequest(array $values): void
    {
        if (array_key_exists('frequencyData', $values)) {
            $typedFrequency = new frequencies\Once();

            // When saving, pluck just the frequency data we want, and typecast properly
            $frequencyType = $values['frequencyData']['type'] ?? null;
            $frequencyData = $values['frequencyData'][$frequencyType] ?? [];

            if ($frequencyType) {
                if ($frequency = Events::$plugin->getSessions()->getFrequencyById($frequencyType)) {
                    // Create a new class of the same type, just in case
                    $typedFrequency = new (get_class($frequency))($frequencyData);
                }
            }

            $values['frequency'] = $typedFrequency;
        }

        if (array_key_exists('occurrenceRange', $values)) {
            $values['occurrenceRange'] = new OccurrenceRange($values['occurrenceRange']);
        }

        parent::setAttributesFromRequest($values);
    }

    public function getIsRecurring(): bool
    {
        return (bool)$this->groupUid;
    }

    public function getIsAvailable(): bool
    {
        if ($this->getPrimaryOwner()->getIsDraft()) {
            return false;
        }

        if ($this->getPrimaryOwner()->status != Event::STATUS_LIVE) {
            return false;
        }

        return parent::getIsAvailable();
    }

    public function getFieldLayout(): ?FieldLayout
    {
        $fieldLayout = parent::getFieldLayout();

        if (!$fieldLayout && $this->getOwnerId()) {
            $fieldLayout = $this->getOwner()->getType()->getSessionFieldLayout();
            $this->fieldLayoutId = $fieldLayout->id;
        }

        return $fieldLayout;
    }

    public function setPrimaryOwner(?ElementInterface $owner): void
    {
        if (!$owner instanceof Event) {
            throw new InvalidArgumentException('Event sessions can only be assigned to events.');
        }

        if ($owner->siteId) {
            $this->siteId = $owner->siteId;
        }

        $this->fieldLayoutId = $owner->getType()->sessionFieldLayoutId;

        $this->traitSetPrimaryOwner($owner);
    }

    public function setOwner(?ElementInterface $owner): void
    {
        if (!$owner instanceof Event) {
            throw new InvalidArgumentException('Event sessions can only be assigned to events.');
        }

        if ($owner->siteId) {
            $this->siteId = $owner->siteId;
        }

        $this->fieldLayoutId = $owner->getType()->sessionFieldLayoutId;

        $this->traitSetOwner($owner);
    }

    public function setEventSlug(?string $eventSlug): void
    {
        $this->_eventSlug = $eventSlug;
    }

    public function getEventSlug(): ?string
    {
        if ($this->_eventSlug === null) {
            $event = $this->getOwner();

            $this->_eventSlug = $event?->slug ?? null;
        }

        return $this->_eventSlug;
    }

    public function setEventTypeHandle(?string $eventTypeHandle): void
    {
        $this->_eventTypeHandle = $eventTypeHandle;
    }

    public function getEventTypeHandle(): ?string
    {
        if ($this->_eventTypeHandle === null) {
            $event = $this->getOwner();

            $this->_eventTypeHandle = $event ? ($event->getType()?->handle ?? null) : null;
        }

        return $this->_eventTypeHandle;
    }

    public function updateTitle(Event $event): void
    {
        // Ensure that we execute any code within the correct language
        LocaleHelper::switchAppLanguage($this->getSite()->language, null, function() use ($event) {
            $title = Craft::$app->getView()->renderObjectTemplate($event->getType()->sessionTitleFormat, $this);

            // Direct DB update, as this is `afterSave`
            Db::update(Table::ELEMENTS_SITES, ['title' => $title], ['elementId' => $this->id, 'siteId' => $this->siteId]);

            $this->title = $title;
        });
    }

    public function getTickets(): array
    {
        return Ticket::find()->eventId($this->primaryOwnerId)->sessionId($this->id)->all();
    }

    public function getSidebarHtml(bool $static): string
    {
        $view = Craft::$app->getView();
        $containerId = 'events-session-pane';
        $id = $view->namespaceInputId($containerId);

        $view->registerAssetBundle(SessionEditAsset::class);

        $js = <<<JS
            (() => { new Craft.Events.SessionEdit('$id'); })();
        JS;

        $view->registerJs($js, $view::POS_END);

        $html = Html::tag('div', null, ['id' => $containerId]);

        if ($this->getIsRecurring()) {
            $occurrenceRange = $this->occurrenceRange ?? new OccurrenceRange();

            $html .= $occurrenceRange->getInputHtml($this);
        }

        $html .= parent::getSidebarHtml($static);

        return $html;
    }

    public function generateRecurringSessions(): void
    {
        // Don't generate recurring sessions if it isn't a recurring session
        if (!$this->frequency->isRecurring()) {
            return;
        }

        // Get the calculated dates to create new sessions for
        if ($dates = $this->frequency->getRecurringSessionDates($this)) {
            // Generate a unique token that groups all sessions we're about to create together, so we can edit things in bulk later.
            $groupUid = StringHelper::UUID();

            foreach ($dates as $date) {
                // Create new elements, based off this main session
                Craft::$app->getElements()->duplicateElement($this, [
                    'startDate' => $date['startDate'],
                    'endDate' => $date['endDate'],
                    'groupUid' => $groupUid,

                    // Important to empty the frequency data, else we'll infinite loop
                    'frequency' => new frequencies\Once(),
                ]);
            }

            // Update the main session, to include a reference to `groupUid` to group with recurring sessions.
            Db::update('{{%events_sessions}}', ['groupUid' => $groupUid], ['id' => $this->id]);
        }
    }

    public function getDateSummary(): string
    {
        $parts = [];
        $formatter = Craft::$app->getFormatter();

        if ($this->startDate && $this->endDate) {
            $startDateDate = $formatter->asDate($this->startDate, Formatter::FORMAT_WIDTH_SHORT);
            $startDateTime = $formatter->asTime($this->startDate, Formatter::FORMAT_WIDTH_SHORT);
            $endDateDate = $formatter->asDate($this->endDate, Formatter::FORMAT_WIDTH_SHORT);
            $endDateTime = $formatter->asTime($this->endDate, Formatter::FORMAT_WIDTH_SHORT);

            $combineTime = false;

            if ($startDateDate === $endDateDate) {
                // Same day, format the output accordingly
                $combineTime = true;
            } else {
                // Check if the second date is exactly midnight of the next day
                $midnight = (clone $this->startDate)->setTime(0, 0, 0)->modify('+1 day');
                
                if ($this->endDate == $midnight) {
                    // If the second date is midnight of the next day, format the output accordingly
                    $combineTime = true;
                }
            }

            $parts = [
                $startDateDate,
                $startDateTime,
                '—',
            ];

            if (!$combineTime) {
                $parts[] = $endDateDate;
            }
            $parts[] = $endDateTime;
        }

        return implode(' ', $parts);
    }

    public function getGqlTypeName(): string
    {
        $event = $this->getOwner();

        if (!$event) {
            return 'Session';
        }

        try {
            $eventType = $event->getType();
        } catch (Exception) {
            return 'Session';
        }

        return static::gqlTypeNameByContext($eventType);
    }

    public function beforeValidate(): bool
    {
        // Generate a placeholder title, as it'll be added after save
        if (!$this->id || $this->getIsUnpublishedDraft()) {
            $this->title = Craft::t('app', 'New {type}', [
                'type' => static::lowerDisplayName(),
            ]);
        } else {
            $this->title = sprintf('%s %s', static::displayName(), $this->id);
        }

        return parent::beforeValidate();
    }

    public function beforeSave(bool $isNew): bool
    {
        $event = $this->getOwner();

        // Set the field layout
        $eventType = $event->getType();
        $this->fieldLayoutId = $eventType->sessionFieldLayoutId;

        return parent::beforeSave($isNew);
    }

    public function afterSave(bool $isNew): void
    {
        $ownerId = $this->getOwnerId();

        if (!$this->propagating) {
            if (!$isNew) {
                $record = SessionRecord::findOne($this->id);

                if (!$record) {
                    throw new Exception('Invalid session id: ' . $this->id);
                }
            } else {
                $record = new SessionRecord();
                $record->id = $this->id;
            }

            // Save the current ID for later, in this is a draft and there are recurring sessions that need updating
            $currentId = $this->id;

            // Special-case for editing recurring sessions, where we want to only update sessions for custom start/end dates.
            // The `OccurenceRange` class will take care of saving this session if it's within the range.
            $shouldSaveRecord = true;

            if ($this->occurrenceRange && $this->occurrenceRange->type === OccurrenceRange::TYPE_CUSTOM) {
                $shouldSaveRecord = false;
            }

            if ($shouldSaveRecord) {
                $record->primaryOwnerId = $this->getPrimaryOwnerId();
                $record->startDate = $this->startDate;
                $record->endDate = $this->endDate;
                $record->allDay = $this->allDay;
                $record->groupUid = $this->groupUid;

                // We want to always have the same date as the element table, based on the logic for updating these in the element service i.e resaving
                $record->dateUpdated = $this->dateUpdated;
                $record->dateCreated = $this->dateCreated;

                $record->save(false);

                // Update the title, based on the start/end dates
                $this->updateTitle($this->getOwner());
            }

            $this->id = $record->id;

            if ($shouldSaveRecord && $ownerId && $this->saveOwnership) {
                if (!isset($this->sortOrder) && (!$isNew || $this->duplicateOf)) {
                    // figure out if we should proceed this way
                    // if we're dealing with an element that's being duplicated, and it has a draftId
                    // it means we're creating a draft of something
                    // if we're duplicating element via duplicate action - draftId would be empty
                    // Same as https://github.com/craftcms/cms/pull/14497/files
                    $elementId = null;

                    if ($this->duplicateOf) {
                        if ($this->draftId) {
                            $elementId = $this->duplicateOf->id;
                        }
                    } else {
                        // if we're not duplicating - use element's id
                        $elementId = $this->id;
                    }

                    if ($elementId) {
                        $this->sortOrder = (new Query())
                            ->select('sortOrder')
                            ->from(Table::ELEMENTS_OWNERS)
                            ->where([
                                'elementId' => $elementId,
                                'ownerId' => $ownerId,
                            ])
                            ->scalar() ?: null;
                    }
                }

                if (!isset($this->sortOrder)) {
                    $max = (new Query())
                        ->from(['eo' => Table::ELEMENTS_OWNERS])
                        ->innerJoin(['s' => '{{%events_sessions}}'], '[[s.id]] = [[eo.elementId]]')
                        ->where([
                            'eo.ownerId' => $ownerId,
                        ])
                        ->max('[[eo.sortOrder]]');
                    $this->sortOrder = $max ? $max + 1 : 1;
                }

                if ($isNew) {
                    Db::insert(Table::ELEMENTS_OWNERS, [
                        'elementId' => $this->id,
                        'ownerId' => $ownerId,
                        'sortOrder' => $this->sortOrder,
                    ]);
                } else {
                    Db::update(Table::ELEMENTS_OWNERS, [
                        'sortOrder' => $this->sortOrder,
                    ], [
                        'elementId' => $this->id,
                        'ownerId' => $ownerId,
                    ]);
                }
            }
        }

        parent::afterSave($isNew);

        // When we've saved a brand-new session for the first time, we want to generate any recurring sessions as new elements.
        if (!$this->propagating) {
            if ($this->getIsFresh() && !$this->getIsDraft()) {
                $this->generateRecurringSessions();
            }

            // Check if we should update any other recurring sessions
            $this->occurrenceRange?->updateSessions($this);
        }
    }

    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        Db::update('{{%events_tickets}}', ['deletedWithSession' => true], ['sessionId' => $this->id]);

        return true;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['startDate', 'endDate'], 'required', 'on' => self::SCENARIO_LIVE];
        $rules[] = [['startDate', 'endDate'], DateTimeValidator::class];

        $rules[] = [
            ['startDate'], function($attribute) {
                if ($this->startDate > $this->endDate) {
                    $this->addError('startDate', Craft::t('events', 'Start Date must be before End Date'));
                }
            },
        ];

        $rules[] = [
            ['endDate'], function($attribute) {
                if ($this->endDate < $this->startDate) {
                    $this->addError('endDate', Craft::t('events', 'End Date must be before Start Date'));
                }
            },
        ];

        $rules[] = [
            ['frequency'], function($model) {
                if ($this->frequency->isRecurring() && !$this->frequency->validate()) {
                    foreach ($this->frequency->getErrors() as $key => $errors) {
                        // Namespace (most) errors to their respective type
                        $errorKey = $key === 'type' ? "frequencyData.$key" : "frequencyData.{$this->frequency::id()}.$key";

                        foreach ($errors as $error) {
                            $this->addError($errorKey, $error);
                        }
                    }
                }
            }, 'on' => self::SCENARIO_LIVE,
        ];

        $rules[] = [
            ['occurrenceRange'], function($model) {
                if ($this->occurrenceRange && !$this->occurrenceRange->validate()) {
                    foreach ($this->occurrenceRange->getErrors() as $key => $errors) {
                        foreach ($errors as $error) {
                            $this->addError("occurrenceRange.$key", $error);
                        }
                    }
                }
            }, 'on' => self::SCENARIO_LIVE,
        ];

        $rules[] = [['ownerId', 'primaryOwnerId', 'allDay'], 'safe'];

        return $rules;
    }

    protected function attributeHtml(string $attribute): string
    {
        if ($attribute === 'startDate' || $attribute === 'endDate') {
            $value = $this->$attribute;
            $formatter = Craft::$app->getFormatter();

            return Html::tag('span', $formatter->asDatetime($value, Locale::LENGTH_SHORT));
        }

        if ($attribute === 'recurring') {
            $value = $this->getIsRecurring();

            if ($value) {
                return Html::tag('span', '', [
                    'class' => 'checkbox-icon',
                    'role' => 'img',
                    'title' => Craft::t('app', 'Enabled'),
                    'aria' => [
                        'label' => Craft::t('app', 'Enabled'),
                    ],
                ]);
            } else {
                return '';
            }
        }

        if ($attribute === 'allDay') {
            $value = $this->$attribute;

            if ($value) {
                return Html::tag('span', '', [
                    'class' => 'checkbox-icon',
                    'role' => 'img',
                    'title' => Craft::t('app', 'Enabled'),
                    'aria' => [
                        'label' => Craft::t('app', 'Enabled'),
                    ],
                ]);
            } else {
                return '';
            }
        }

        return parent::attributeHtml($attribute);
    }

    protected function inlineAttributeInputHtml(string $attribute): string
    {
        if ($attribute === 'startDate') {
            return Cp::dateTimeFieldHtml([
                'name' => 'startDate',
                'value' => $this->startDate,
            ]);
        }

        if ($attribute === 'endDate') {
            return Cp::dateTimeFieldHtml([
                'name' => 'endDate',
                'value' => $this->endDate,
            ]);
        }

        return parent::inlineAttributeInputHtml($attribute);
    }

    protected function htmlAttributes(string $context): array
    {
        $attributes = [];

        if ($this->getIsRecurring()) {
            $attributes['data']['recurring'] = true;
        }

        return $attributes;
    }

    protected function destructiveActionMenuItems(): array
    {
        if (!$this->id || $this->getIsUnpublishedDraft() || !$this->getIsRecurring()) {
            return parent::destructiveActionMenuItems();
        }

        // Intentionally not calling parent::destructiveActionMenuItems() here,
        // because we want to override the core deletion UX.
        $items = [];

        $view = Craft::$app->getView();
        $deleteId = sprintf('action-delete-%s', mt_rand());

        $items[] = [
            'id' => $deleteId,
            'icon' => 'trash',
            'label' => Craft::t('app', 'Delete {type}', [
                'type' => static::lowerDisplayName(),
            ]),
        ];

        $view->registerAssetBundle(SessionIndexAsset::class);

        $view->registerJsWithVars(fn($id, $sessionId) => <<<JS
            $('#' + $id).on('activate', () => {
                new Craft.Events.DeleteSessionModal($sessionId);
            });
        JS, [
            $view->namespaceInputId($deleteId),
            $this->id,
        ]);

        return $items;
    }

    protected function cacheTags(): array
    {
        return [
            "event:$this->primaryOwnerId",
        ];
    }

    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('events/sessions/' . $this->id);
    }

}
