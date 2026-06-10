<?php
namespace verbb\events\elements;

use verbb\events\Events;
use verbb\events\elements\db\TicketTypeQuery;
use verbb\events\elements\traits\PurchasedTicketTrait;
use verbb\events\helpers\TicketHelper;
use verbb\events\models\EventType;
use verbb\events\records\TicketType as TicketTypeRecord;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use craft\base\NestedElementTrait;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\EagerLoadPlan;
use craft\elements\User;
use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\MoneyHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\DateTimeValidator;

use craft\commerce\Plugin as Commerce;
use craft\commerce\behaviors\CurrencyAttributeBehavior;
use craft\commerce\helpers\Currency;

use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

use DateTime;
use Money\Money;

class TicketType extends Element implements NestedElementInterface
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('events', 'Ticket Type');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('events', 'ticket type');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('events', 'Ticket Types');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('events', 'ticket types');
    }

    public static function refHandle(): ?string
    {
        return 'ticketType';
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

    public static function find(): TicketTypeQuery
    {
        return new TicketTypeQuery(static::class);
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return $context->handle . '_TicketType';
    }

    public static function gqlScopesByContext(mixed $context): array
    {
        return ['eventsEventTypes.' . $context->uid];
    }

    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        if (in_array($handle, ['event', 'owner', 'primaryOwner'])) {
            // Get the source element IDs
            $sourceElementIds = [];

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            $map = (new Query())
                ->select('id as source, primaryOwnerId as target')
                ->from('{{%events_ticket_types}}')
                ->where(['in', 'id', $sourceElementIds])
                ->all();

            return [
                'elementType' => Event::class,
                'map' => $map,
                'criteria' => [
                    'status' => null,
                ],
            ];
        }

        return self::traitEagerLoadingMap($sourceElements, $handle);
    }

    protected static function defineFieldLayouts(?string $source): array
    {
        if ($source === null || $source === '*') {
            $eventTypes = Events::$plugin->getEventTypes()->getAllEventTypes();
        } else {
            $eventTypes = [];

            if (preg_match('/^eventType:(.+)$/', $source, $matches)) {
                $eventType = Events::$plugin->getEventTypes()->getEventTypeByUid($matches[1]);
                
                if ($eventType) {
                    $eventTypes[] = $eventType;
                }
            }
        }

        return array_map(fn(EventType $eventType) => $eventType->getTicketTypeFieldLayout(), $eventTypes);
    }

    protected static function defineSources(string $context = null): array
    {
        $eventTypes = Events::$plugin->getEventTypes()->getViewableEventTypes();

        $sources = [
            [
                'key' => '*',
                'label' => Craft::t('events', 'All ticket types'),
            ],
        ];

        foreach ($eventTypes as $eventType) {
            $sources[] = ['heading' => $eventType->name];

            $events = Event::find()->typeId($eventType->id)->all();

            foreach ($events as $event) {
                $key = 'event:' . $event->uid;

                $sources[] = [
                    'key' => $key,
                    'label' => $event->title,
                    'criteria' => [
                        'eventId' => $event->id,
                    ],
                ];
            }
        }

        return $sources;
    }

    protected static function includeSetStatusAction(): bool
    {
        return true;
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['price', 'capacity'];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('events', 'Title'),
            'price' => Craft::t('events', 'Price'),
            'capacity' => Craft::t('events', 'Ticket Capacity'),
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'event' => ['label' => Craft::t('events', 'Event')],
            'price' => ['label' => Craft::t('events', 'Price')],
            'capacity' => ['label' => Craft::t('events', 'Ticket Capacity')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['price', 'capacity'];
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

    public ?int $capacity = null;
    public ?DateTime $availableFrom = null;
    public ?DateTime $availableTo = null;
    public ?int $minQty = null;
    public ?int $maxQty = null;
    public bool $promotable = true;
    public int $seatsPerTicket = 1;
    public ?int $sortOrder = null;
    public bool $deletedWithEvent = false;
    
    private ?float $_price = null;
    private ?TicketCollection $_tickets = null;
    private ?string $_eventSlug = null;
    private ?string $_eventTypeHandle = null;


    // Public Methods
    // =========================================================================

    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        $event = $this->getOwner();

        if ($event === null) {
            return false;
        }

        try {
            $eventType = $event->getType();
        } catch (Exception) {
            return false;
        }

        return $user->can("events-viewTicketTypes:{$eventType->uid}");
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

        try {
            $eventType = $event->getType();
        } catch (Exception) {
            return false;
        }

        return $user->can("events-editTicketTypes:{$eventType->uid}");
    }

    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        $event = $this->getOwner();

        if ($event === null) {
            return false;
        }

        try {
            $eventType = $event->getType();
        } catch (Exception) {
            return false;
        }

        return $user->can("events-deleteTicketTypes:{$eventType->uid}");
    }

    public function canDuplicate(User $user): bool
    {
        if (parent::canDuplicate($user)) {
            return true;
        }

        return $this->canSave($user);
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['currencyAttributes'] = [
            'class' => CurrencyAttributeBehavior::class,
            'defaultCurrency' => Commerce::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso(),
            'currencyAttributes' => $this->currencyAttributes(),
        ];

        return $behaviors;
    }

    public function safeAttributes()
    {
        $attributes = parent::safeAttributes();
        $attributes[] = 'eventId';

        return $attributes;
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'event';

        return $attributes;
    }

    public function currencyAttributes(): array
    {
        return ['price'];
    }

    public function setPrice(Money|array|float|int|null $price): void
    {
        if (is_array($price)) {
            if (isset($price['value']) && $price['value'] === '') {
                $this->_price = null;
                return;
            }

            if (!isset($price['locale'])) {
                $price['locale'] = Craft::$app->getFormattingLocale()->id;
            }

            if (!isset($price['currency'])) {
                $store = Commerce::getInstance()->getStores()->getStoreBySiteId($this->siteId);

                $price['currency'] = $store->getCurrency();
            }

            $price = MoneyHelper::toMoney($price);

            // nullify if conversion fails
            $price = $price ?: null;
        }

        if ($price instanceof Money) {
            $price = MoneyHelper::toDecimal($price);
        } elseif ($price !== null) {
            $price = (float)$price;
        }

        $this->_price = $price;
    }

    public function getPrice(): ?float
    {
        return $this->_price;
    }

    public function getPriceForInput(): ?string
    {
        if ($this->price === null) {
            return null;
        }

        $store = Commerce::getInstance()->getStores()->getStoreBySiteId($this->siteId);
        $currency = $store->getCurrency();
        $money = Commerce::getInstance()->getCurrencies()->getTeller($currency)->convertToMoney($this->price);

        return MoneyHelper::toNumber($money) ?: null;
    }

    public function getIsAvailable(): bool
    {
        if (!$this->getPrimaryOwner()) {
            return false;
        }

        if (!$this->getPrimaryOwner()->getIsAvailable()) {
            return false;
        }

        if ($this->getPrimaryOwner()->getIsDraft()) {
            return false;
        }

        if ($this->getPrimaryOwner()->status != Event::STATUS_LIVE) {
            return false;
        }

        return !$this->getAvailableTickets()->isEmpty();
    }

    public function getAvailableTickets(): TicketCollection
    {
        $tickets = $this->getTickets();

        foreach ($tickets as $key => $ticket) {
            if (!$ticket->getIsAvailable()) {
                unset($tickets[$key]);
            }
        }

        return $tickets;
    }

    public function getFieldLayout(): ?FieldLayout
    {
        $fieldLayout = parent::getFieldLayout();

        if ($fieldLayout) {
            // Ticket Type field layouts are stored on the event type so retrieving the field layout by ID does not set the provider
            $eventType = collect(Events::$plugin->getEventTypes()->getAllEventTypes())->firstWhere('ticketTypeFieldLayoutId', $fieldLayout->id);
            
            if ($eventType) {
                $fieldLayout->provider = $eventType;

                return $fieldLayout;
            }
        }

        try {
            if ($this->getOwner() === null) {
                return parent::getFieldLayout();
            }

            return $this->getOwner()->getType()->getTicketTypeFieldLayout();
        } catch (InvalidConfigException) {
            // The event type was probably deleted
            return null;
        }
    }

    public function setPrimaryOwner(?ElementInterface $owner): void
    {
        if (!$owner instanceof Event) {
            throw new InvalidArgumentException('Event tickets can only be assigned to events.');
        }

        if ($owner->siteId) {
            $this->siteId = $owner->siteId;
        }

        $this->fieldLayoutId = $owner->getType()->ticketTypeFieldLayoutId;

        $this->traitSetPrimaryOwner($owner);
    }

    public function setOwner(?ElementInterface $owner): void
    {
        if (!$owner instanceof Event) {
            throw new InvalidArgumentException('Event tickets can only be assigned to events.');
        }

        if ($owner->siteId) {
            $this->siteId = $owner->siteId;
        }

        $this->fieldLayoutId = $owner->getType()->ticketTypeFieldLayoutId;

        $this->traitSetOwner($owner);
    }

    public function getPrimaryOwner(): ?Event
    {
        // TODO remove implementation when `NestedElementTrait::getOwner()` is updated
        if (!isset($this->_primaryOwner)) {
            $primaryOwnerId = $this->getPrimaryOwnerId();

            if (!$primaryOwnerId) {
                return null;
            }

            $this->_primaryOwner = Craft::$app->getElements()->getElementById($primaryOwnerId, Event::class, $this->siteId, [
                'trashed' => null,
            ]) ?? false;

            if (!$this->_primaryOwner) {
                throw new InvalidConfigException("Invalid owner ID: $primaryOwnerId");
            }
        }

        /** @phpstan-ignore-next-line */
        return $this->_primaryOwner ?: null;
    }

    public function getOwner(): ?Event
    {
        // TODO remove implementation when `NestedElementTrait::getOwner()` is updated
        if (!isset($this->_owner)) {
            $ownerId = $this->getOwnerId();

            if (!$ownerId) {
                return null;
            }

            // If ownerId and primaryOwnerId are the same, return the primary owner
            if ($ownerId === $this->getPrimaryOwnerId()) {
                return $this->getPrimaryOwner();
            }

            $this->_owner = Craft::$app->getElements()->getElementById($ownerId, Event::class, $this->siteId, [
                'trashed' => null,
            ]) ?? false;

            if (!$this->_owner) {
                throw new InvalidConfigException("Invalid owner ID: $ownerId");
            }
        }

        return $this->_owner ?: null;
    }

    public function getEvent(): ?Event
    {
        return $this->getOwner();
    }

    public function setEvent(Event $event): void
    {
        $this->setOwner($event);
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

    public function getTickets(): TicketCollection
    {
        if ($this->_tickets) {
            return $this->_tickets;
        }

        return $this->_tickets = Ticket::find()
            ->eventId($this->primaryOwnerId)
            ->typeId($this->id)
            ->collect();
    }

    public function getSupportedSites(): array
    {
        $owner = $this->getOwner();

        if (!$owner) {
            return [Craft::$app->getSites()->getPrimarySite()->id];
        }

        return $owner->getSupportedSites();
    }

    public function getGqlTypeName(): string
    {
        $event = $this->getOwner();

        if (!$event) {
            return 'TicketType';
        }

        try {
            $eventType = $event->getType();
        } catch (Exception) {
            return 'TicketType';
        }

        return static::gqlTypeNameByContext($eventType);
    }

    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        if (in_array($handle, ['event', 'owner', 'primaryOwner'])) {
            $event = $elements[0] ?? null;
            
            if ($event instanceof Event) {
                if ($handle == 'primaryOwner') {
                    $this->setPrimaryOwner($event);
                } else {
                    $this->setOwner($event);
                }
            }
        } else {
            $this->traitSetEagerLoadedElements($handle, $elements, $plan);
        }
    }

    public function beforeSave(bool $isNew): bool
    {
        // Set the field layout
        if ($event = $this->getOwner()) {
            $eventType = $event->getType();
            $this->fieldLayoutId = $eventType->ticketTypeFieldLayoutId;
        }

        return parent::beforeSave($isNew);
    }

    public function afterSave(bool $isNew): void
    {
        $ownerId = $this->getOwnerId();

        if (!$this->propagating) {
            if (!$isNew) {
                $record = TicketTypeRecord::findOne($this->id);

                if (!$record) {
                    throw new Exception('Invalid ticket id: ' . $this->id);
                }
            } else {
                $record = new TicketTypeRecord();
                $record->id = $this->id;
            }

            $record->primaryOwnerId = $this->getPrimaryOwnerId();
            $record->capacity = $this->capacity;
            $record->price = $this->price;
            $record->availableFrom = $this->availableFrom;
            $record->availableTo = $this->availableTo;

            // Use property checks, rather than PC `schemaVersion` checks for performance
            if (array_key_exists('minQty', $record->getAttributes())) {
                $record->minQty = $this->minQty;
            }

            if (array_key_exists('maxQty', $record->getAttributes())) {
                $record->maxQty = $this->maxQty;
            }

            if (array_key_exists('promotable', $record->getAttributes())) {
                $record->promotable = $this->promotable;
            }

            if (array_key_exists('seatsPerTicket', $record->getAttributes())) {
                $record->seatsPerTicket = $this->seatsPerTicket;
            }

            // We want to always have the same date as the element table, based on the logic for updating these in the element service i.e resaving
            $record->dateUpdated = $this->dateUpdated;
            $record->dateCreated = $this->dateCreated;

            $record->save(false);

            $this->id = $record->id;

            if ($ownerId && $this->saveOwnership) {
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
                        ->innerJoin(['t' => '{{%events_ticket_types}}'], '[[t.id]] = [[eo.elementId]]')
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
    }

    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        Db::update('{{%events_tickets}}', ['deletedWithType' => true], ['typeId' => $this->id]);

        return true;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['price'], 'required', 'on' => self::SCENARIO_LIVE];
        $rules[] = [['capacity'], 'number', 'integerOnly' => true];
        $rules[] = [['minQty', 'maxQty', 'seatsPerTicket'], 'number', 'integerOnly' => true, 'skipOnEmpty' => true];
        $rules[] = [['price'], 'number'];
        $rules[] = [['availableFrom', 'availableTo'], DateTimeValidator::class];

        $rules[] = [
            ['availableFrom'], function($model) {
                if ($this->availableFrom && $this->availableTo && $this->availableFrom > $this->availableTo) {
                    $this->addError('availableFrom', Craft::t('events', 'Available From must be before Available To'));
                }
            },
        ];

        $rules[] = [
            ['availableTo'], function($model) {
                if ($this->availableTo && $this->availableFrom && $this->availableTo < $this->availableFrom) {
                    $this->addError('availableTo', Craft::t('events', 'Available To must be before Available From'));
                }
            },
        ];

        $rules[] = [['ownerId', 'primaryOwnerId', 'promotable'], 'safe'];

        return $rules;
    }

    protected function metaFieldsHtml(bool $static): string
    {
        $fields = [];
        $view = Craft::$app->getView();

        $isDeltaRegistrationActive = $view->getIsDeltaRegistrationActive();
        $view->setIsDeltaRegistrationActive(true);
        $view->registerDeltaName('availableFrom');
        $view->registerDeltaName('availableTo');
        $view->setIsDeltaRegistrationActive($isDeltaRegistrationActive);

        // Post Date
        $fields[] = Cp::dateTimeFieldHtml([
            'status' => $this->getAttributeStatus('availableFrom'),
            'label' => Craft::t('events', 'Available From'),
            'id' => 'availableFrom',
            'name' => 'availableFrom',
            'value' => $this->availableFrom,
            'errors' => $this->getErrors('availableFrom'),
            'disabled' => $static,
        ]);

        // Expiry Date
        $fields[] = Cp::dateTimeFieldHtml([
            'status' => $this->getAttributeStatus('availableTo'),
            'label' => Craft::t('events', 'Available To'),
            'id' => 'availableTo',
            'name' => 'availableTo',
            'value' => $this->availableTo,
            'errors' => $this->getErrors('availableTo'),
            'disabled' => $static,
        ]);

        $fields[] = parent::metaFieldsHtml($static);

        return implode("\n", $fields);
    }

    protected function attributeHtml(string $attribute): string
    {
        if ($attribute === 'price') {
            return Currency::formatAsCurrency($this->price);
        }

        return parent::attributeHtml($attribute);
    }

    protected function inlineAttributeInputHtml(string $attribute): string
    {
        if ($attribute === 'price') {
            $store = Commerce::getInstance()->getStores()->getStoreBySiteId($this->siteId);
            $currency = $store->getCurrency();

            return Cp::moneyInputHtml([
                'name' => 'price',
                'value' => $this->getPriceForInput(),
                'currency' => $currency,
                'decimals' => Commerce::getInstance()->getCurrencies()->getSubunitFor($currency),
            ]);
        }

        if ($attribute === 'capacity') {
            return Cp::textHtml([
                'name' => 'capacity',
                'value' => $this->capacity,
            ]);
        }

        return parent::inlineAttributeInputHtml($attribute);
    }

    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('events/ticket-types/' . $this->id);
    }

    protected function ownerType(): ?string
    {
        return Event::class;
    }

}
