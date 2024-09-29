<?php
namespace verbb\events\elements;

use verbb\events\elements\db\TicketQuery;
use verbb\events\events\CustomizeEventSnapshotDataEvent;
use verbb\events\events\CustomizeEventSnapshotFieldsEvent;
use verbb\events\events\CustomizeTicketSnapshotDataEvent;
use verbb\events\events\CustomizeTicketSnapshotFieldsEvent;
use verbb\events\helpers\TicketHelper;
use verbb\events\records\Ticket as TicketRecord;

use Craft;
use craft\base\Element;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Html;
use craft\helpers\StringHelper;

use craft\commerce\base\Purchasable;
use craft\commerce\behaviors\CurrencyAttributeBehavior;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;

use yii\base\Exception;

use Throwable;

class Ticket extends Purchasable
{
    // Constants
    // =========================================================================

    public const EVENT_AFTER_CAPTURE_EVENT_SNAPSHOT = 'afterCaptureEventSnapshot';
    public const EVENT_AFTER_CAPTURE_TICKET_SNAPSHOT = 'afterCaptureTicketSnapshot';
    public const EVENT_BEFORE_CAPTURE_EVENT_SNAPSHOT = 'beforeCaptureEventSnapshot';
    public const EVENT_BEFORE_CAPTURE_TICKET_SNAPSHOT = 'beforeCaptureTicketSnapshot';


    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('events', 'Ticket');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('events', 'Tickets');
    }

    public static function refHandle(): ?string
    {
        return 'ticket';
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function find(): TicketQuery
    {
        return new TicketQuery(static::class);
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return $context->handle . '_Ticket';
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
                'label' => Craft::t('events', 'All tickets'),
            ],
        ];
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['sku', 'price', 'capacity'];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('events', 'Title'),
            'sku' => Craft::t('events', 'SKU'),
            // 'price' => Craft::t('events', 'Price'),
            // 'capacity' => Craft::t('events', 'Capacity'),
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'event' => ['label' => Craft::t('events', 'Event')],
            'session' => ['label' => Craft::t('events', 'Session')],
            'type' => ['label' => Craft::t('events', 'Type')],
            'sku' => ['label' => Craft::t('events', 'SKU')],
            'price' => ['label' => Craft::t('events', 'Price')],
            'capacity' => ['label' => Craft::t('events', 'Capacity')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['price', 'capacity'];
    }


    // Properties
    // =========================================================================

    public ?int $eventId = null;
    public ?int $sessionId = null;
    public ?int $typeId = null;

    private ?Event $_event = null;
    private ?Session $_session = null;
    private ?TicketType $_type = null;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        // Always enable tracking of stock (for now)
        $this->inventoryTracked = true;

        if ($eventType = $this->getEvent()?->getType()) {
            try {
                // Title and SKU are dynamic
                $this->title = $this->getDescription();

                if (!$this->sku && $eventType->ticketSkuFormat) {
                    $this->sku = Craft::$app->getView()->renderObjectTemplate($eventType->ticketSkuFormat, $this);
                }
            } catch (Throwable $e) {
            }
        }

        // Set purchasable properties from the ticket type
        $this->minQty = $this->getType()?->minQty ?? null;
        $this->maxQty = $this->getType()?->maxQty ?? null;

        parent::init();
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['currencyAttributes'] = [
            'class' => CurrencyAttributeBehavior::class,
            'currencyAttributes' => $this->currencyAttributes(),
        ];

        return $behaviors;
    }

    public function getDescription(): string
    {
        if ($eventType = $this->getEvent()?->getType()) {
            try {
                return Craft::$app->getView()->renderObjectTemplate($eventType->ticketTitleFormat, $this);
            } catch (Throwable $e) {
            }
        }

        return parent::getDescription();
    }

    public function getEvent(): ?Event
    {
        if (!$this->_event) {
            if ($this->eventId) {
                $this->_event = Event::find()->id($this->eventId)->one();
            } else {
                return null;
            }
        }

        return $this->_event;
    }

    public function setEvent(Event $event): void
    {
        $this->_event = $event;
        $this->eventId = $event->id;
    }

    public function getSession(): ?Session
    {
        if (!$this->_session) {
            if ($this->sessionId) {
                $this->_session = Session::find()->id($this->sessionId)->one();
            } else {
                return null;
            }
        }

        return $this->_session;
    }

    public function setSession(Session $session): void
    {
        $this->_session = $session;
        $this->sessionId = $session->id;
    }

    public function getType(): ?TicketType
    {
        if (!$this->_type) {
            if ($this->typeId) {
                $this->_type = TicketType::find()->id($this->typeId)->one();
            } else {
                return null;
            }
        }

        return $this->_type;
    }

    public function setType(TicketType $type): void
    {
        $this->_type = $type;
        $this->typeId = $type->id;
    }

    public function getBasePrice(): ?float
    {
        return $this->getType()?->price ?? null;
    }

    public function getStock(): int
    {
        // Available to purchase is capacity (event or ticket) - purchased tickets
        $capacity = $this->getCapacity();
        $purchased = PurchasedTicket::find()->ticketId($this->id)->count();
        
        return $capacity - $purchased;
    }

    public function getCapacity(): int
    {
        $ticketCapacity = $this->getType()?->capacity ?? 0;

        if ($event = $this->getEvent()) {
            if ($this->event->capacity) {
                return min($this->event->capacity, $ticketCapacity);
            }
        }

        return $ticketCapacity;
    }

    public function getIsAvailable(): bool
    {
        if ($type = $this->getType()) {
            $currentTime = DateTimeHelper::currentTimeStamp();

            if ($type->availableFrom) {
                $availableFrom = $type->availableFrom->getTimestamp();

                if ($availableFrom > $currentTime) {
                    return false;
                }
            }

            if ($type->availableTo) {
                $availableTo = $type->availableTo->getTimestamp();

                if ($availableTo < $currentTime) {
                    return false;
                }
            }
        }

        return true;
    }

    public function afterOrderComplete(Order $order, LineItem $lineItem): void
    {
        // Generate purchased tickets
        $elementsService = Craft::$app->getElements();

        for ($i = 0; $i < $lineItem->qty; $i++) {
            $purchasedTicket = new PurchasedTicket();
            $purchasedTicket->eventId = $this->eventId;
            $purchasedTicket->sessionId = $this->sessionId;
            $purchasedTicket->ticketId = $this->id;
            $purchasedTicket->ticketTypeId = $this->typeId;
            $purchasedTicket->orderId = $order->id;
            $purchasedTicket->lineItemId = $lineItem->id;

            // Set the field values from the ticket (handle defaults, and values set on the ticket)
            $purchasedTicket->setFieldValues($this->getSerializedFieldValues());

            // But also allow overriding through the line item options
            foreach ($lineItem->options as $option => $value) {
                // Just catch any errors when trying to set attributes that aren't field handles
                try {
                    $purchasedTicket->setFieldValue($option, $value);
                } catch (Throwable) {
                    continue;
                }
            }

            $elementsService->saveElement($purchasedTicket, false);
        }
    }

    public function getSnapshot(): array
    {
        $data = parent::getSnapshot();
        $data['cpEditUrl'] = $this->getCpEditUrl();

        // Default Event custom field handles
        $eventFields = [];
        $eventFieldsEvent = new CustomizeEventSnapshotFieldsEvent([
            'event' => $this->getEvent(),
            'fields' => $eventFields,
        ]);

        // Allow plugins to modify Event fields to be fetched
        if ($this->hasEventHandlers(self::EVENT_BEFORE_CAPTURE_EVENT_SNAPSHOT)) {
            $this->trigger(self::EVENT_BEFORE_CAPTURE_EVENT_SNAPSHOT, $eventFieldsEvent);
        }

        // Event Attributes
        if ($event = $this->getEvent()) {
            $eventAttributes = $event->attributes();

            // Remove custom fields
            if (($fieldLayout = $event->getFieldLayout()) !== null) {
                foreach ($fieldLayout->getCustomFields() as $field) {
                    ArrayHelper::removeValue($eventAttributes, $field->handle);
                }
            }

            // Add back the custom fields they want
            foreach ($eventFieldsEvent->fields as $field) {
                $eventAttributes[] = $field;
            }

            $data['event'] = $this->getEvent()->toArray($eventAttributes, [], false);

            $eventDataEvent = new CustomizeEventSnapshotDataEvent([
                'event' => $this->getEvent(),
                'fieldData' => $data['event'],
            ]);
        } else {
            $eventDataEvent = new CustomizeEventSnapshotDataEvent([
                'event' => $this->getEvent(),
                'fieldData' => [],
            ]);
        }

        // Allow plugins to modify captured Event data
        if ($this->hasEventHandlers(self::EVENT_AFTER_CAPTURE_EVENT_SNAPSHOT)) {
            $this->trigger(self::EVENT_AFTER_CAPTURE_EVENT_SNAPSHOT, $eventDataEvent);
        }

        $data['event'] = $eventDataEvent->fieldData;

        // Default Ticket custom field handles
        $ticketFields = [];
        $ticketFieldsEvent = new CustomizeTicketSnapshotFieldsEvent([
            'ticket' => $this,
            'fields' => $ticketFields,
        ]);

        // Allow plugins to modify fields to be fetched
        if ($this->hasEventHandlers(self::EVENT_BEFORE_CAPTURE_TICKET_SNAPSHOT)) {
            $this->trigger(self::EVENT_BEFORE_CAPTURE_TICKET_SNAPSHOT, $ticketFieldsEvent);
        }

        $ticketAttributes = $this->attributes();

        // Remove custom fields
        if (($fieldLayout = $this->getFieldLayout()) !== null) {
            foreach ($fieldLayout->getCustomFields() as $field) {
                ArrayHelper::removeValue($ticketAttributes, $field->handle);
            }
        }

        // Add back the custom fields they want
        foreach ($ticketFieldsEvent->fields as $field) {
            $ticketAttributes[] = $field;
        }

        $ticketData = $this->toArray($ticketAttributes, [], false);

        $ticketDataEvent = new CustomizeTicketSnapshotDataEvent([
            'ticket' => $this,
            'fieldData' => $ticketData,
        ]);

        // Allow plugins to modify captured Ticket data
        if ($this->hasEventHandlers(self::EVENT_AFTER_CAPTURE_TICKET_SNAPSHOT)) {
            $this->trigger(self::EVENT_AFTER_CAPTURE_TICKET_SNAPSHOT, $ticketDataEvent);
        }

        return array_merge($ticketDataEvent->fieldData, $data);
    }

    public function getGqlTypeName(): string
    {
        $event = $this->getEvent();

        if (!$event) {
            return 'Ticket';
        }

        try {
            $eventType = $event->getType();
        } catch (Exception) {
            return 'Ticket';
        }

        return static::gqlTypeNameByContext($eventType);
    }

    public function beforeSave(bool $isNew): bool
    {
        $event = $this->getEvent();

        if (!$this->sku) {
            $this->sku = StringHelper::randomString(10);
        }

        // Set the field layout
        $eventType = $event->getType();
        $this->fieldLayoutId = $eventType->sessionFieldLayoutId;

        return parent::beforeSave($isNew);
    }

    public function afterSave(bool $isNew): void
    {
        if (!$isNew) {
            $record = TicketRecord::findOne($this->id);

            if (!$record) {
                throw new Exception('Invalid ticket ID: ' . $this->id);
            }
        } else {
            $record = new TicketRecord();
            $record->id = $this->id;
        }

        $record->eventId = $this->eventId;
        $record->sessionId = $this->sessionId;
        $record->typeId = $this->typeId;

        $record->save(false);

        parent::afterSave($isNew);
    }


    // Protected Methods
    // =========================================================================

    protected function attributeHtml(string $attribute): string
    {
        return match ($attribute) {
            'sku' => Html::encode($this->getSkuAsText()),
            'price' => $this->basePriceAsCurrency,
            default => Element::attributeHtml($attribute),
        };
    }
}
