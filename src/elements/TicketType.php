<?php
namespace verbb\events\elements;

use verbb\events\elements\db\TicketTypeQuery;
use verbb\events\elements\traits\PurchasedTicketTrait;
use verbb\events\helpers\TicketHelper;
use verbb\events\records\TicketType as TicketTypeRecord;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use craft\base\NestedElementTrait;
use craft\db\Query;
use craft\db\Table;
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

    public static function pluralDisplayName(): string
    {
        return Craft::t('events', 'Ticket Types');
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
                'label' => Craft::t('events', 'All ticket types'),
            ],
        ];
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
            'price' => ['label' => Craft::t('events', 'Price')],
            'capacity' => ['label' => Craft::t('events', 'Ticket Capacity')],
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
    public ?int $sortOrder = null;
    public bool $deletedWithEvent = false;
    
    private ?float $_price = null;
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

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['currencyAttributes'] = [
            'class' => CurrencyAttributeBehavior::class,
            'currencyAttributes' => [
                'price',
            ],
        ];

        return $behaviors;
    }

    public function setPrice(Money|array|float|int|null $price): void
    {
        if (is_array($price)) {
            if (isset($price['value']) && $price['value'] === '') {
                $this->_price = null;
                return;
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
            $fieldLayout = $this->getOwner()->getType()->getTicketTypeFieldLayout();
            $this->fieldLayoutId = $fieldLayout->id;
        }

        return $fieldLayout;
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

    public function getTickets(): array
    {
        return Ticket::find()->eventId($this->primaryOwnerId)->typeId($this->id)->all();
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

    public function beforeSave(bool $isNew): bool
    {
        $event = $this->getOwner();

        // Set the field layout
        $eventType = $event->getType();
        $this->fieldLayoutId = $eventType->ticketTypeFieldLayoutId;

        return parent::beforeSave($isNew);
    }

    public function afterSave(bool $isNew): void
    {
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
            $record->minQty = $this->minQty;
            $record->maxQty = $this->maxQty;

            // We want to always have the same date as the element table, based on the logic for updating these in the element service i.e resaving
            $record->dateUpdated = $this->dateUpdated;
            $record->dateCreated = $this->dateCreated;

            $record->save(false);

            $this->id = $record->id;

            $ownerId = $this->getOwnerId();

            if ($ownerId && $this->saveOwnership) {
                if (!isset($this->sortOrder) && !$isNew) {
                    // todo: update based on Entry::afterSave() if we add draft support
                    // (see https://github.com/craftcms/cms/pull/14497)
                    $this->sortOrder = (new Query())
                        ->select('sortOrder')
                        ->from(Table::ELEMENTS_OWNERS)
                        ->where([
                            'elementId' => $this->id,
                            'ownerId' => $ownerId,
                        ])
                        ->scalar() ?: null;
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
        $rules[] = [['minQty', 'maxQty'], 'number', 'integerOnly' => true, 'skipOnEmpty' => true];
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

        $rules[] = [['ownerId', 'primaryOwnerId'], 'safe'];

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
            return Cp::textHtml([
                'name' => 'price',
                'value' => $this->price,
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

    protected function cacheTags(): array
    {
        return [
            "event:$this->primaryOwnerId",
        ];
    }

    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('events/ticket-types/' . $this->id);
    }

}
