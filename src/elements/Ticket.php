<?php
namespace verbb\events\elements;

use verbb\events\Events;
use verbb\events\elements\db\TicketQuery;
use verbb\events\elements\PurchasedTicket;
use verbb\events\events\CustomizeEventSnapshotDataEvent;
use verbb\events\events\CustomizeEventSnapshotFieldsEvent;
use verbb\events\events\CustomizeTicketSnapshotDataEvent;
use verbb\events\events\CustomizeTicketSnapshotFieldsEvent;
use verbb\events\helpers\TicketHelper;
use verbb\events\records\TicketRecord;
use verbb\events\records\PurchasedTicketRecord;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\validators\DateTimeValidator;

use craft\commerce\Plugin as Commerce;
use craft\commerce\base\Purchasable;
use craft\commerce\elements\Order;
use craft\commerce\helpers\Currency;
use craft\commerce\models\LineItem;
use craft\commerce\models\ProductType;
use craft\commerce\models\Sale;

use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\Expression;

class Ticket extends Purchasable
{
    // Constants
    // =========================================================================
  
    const EVENT_BEFORE_CAPTURE_TICKET_SNAPSHOT = 'beforeCaptureTicketSnapshot';
    const EVENT_AFTER_CAPTURE_TICKET_SNAPSHOT = 'afterCaptureTicketSnapshot';
    const EVENT_BEFORE_CAPTURE_EVENT_SNAPSHOT = 'beforeCaptureEventSnapshot';
    const EVENT_AFTER_CAPTURE_EVENT_SNAPSHOT = 'afterCaptureEventSnapshot';


    // Static
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('events', 'Ticket');
    }

    public static function refHandle()
    {
        return 'ticket';
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function find(): ElementQueryInterface
    {
        return new TicketQuery(static::class);
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [[
            'key' => '*',
            'label' => Craft::t('events', 'All events'),
            'defaultSort' => ['postDate', 'desc'],
        ]];

        $events = Event::find()->all();
		
		$type = null;

        foreach ($events as $event) {
			if ($event->type->name != $type) {
				$type = $event->type->name;
				$sources[] = ['heading' => Craft::t('events', '{name} Events', ['name' => $event->type->name])];
			}
            $key = 'event:' . $event->id;

            $sources[] = [
                'key' => $key,
                'label' => $event->title,
                'criteria' => [
                    'eventId' => $event->id,
                ]
            ];
        }

        return $sources;
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['sku', 'price'];
    }


    // Element index methods
    // -------------------------------------------------------------------------

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Title')],
            'event' => ['label' => Craft::t('events', 'Event')],
            'sku' => ['label' => Craft::t('commerce', 'SKU')],
            'price' => ['label' => Craft::t('commerce', 'Price')],
            'quantity' => ['label' => Craft::t('events', 'Quantity')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        $attributes = [];

        if ($source === '*') {
            $attributes[] = 'event';
        }

        $attributes[] = 'title';
        $attributes[] = 'sku';
        $attributes[] = 'price';

        return $attributes;
    }


    // Properties
    // =========================================================================

    public $eventId;
    public $typeId;
    public $sku;
    public $quantity;
    public $price;
    public $availableFrom;
    public $availableTo;
    public $sortOrder;
    public $deletedWithEvent = false;

    private $_event;
    private $_ticketType;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        $this->title = $this->getType()->title ?? '';
    }

    public function __toString(): string
    {
        $event = $this->getEvent();

        if ($event) {
            return "{$this->event}: {$this->getName()}";
        } else {
            return parent::__toString();
        }
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getName(): string
    {
        return $this->title;
    }

    public function rules()
    {
        $rules = parent::rules();

        $rules[] = [['sku'], 'string'];
        $rules[] = [['sku', 'price', 'typeId'], 'required'];
        $rules[] = [['price'], 'number'];

        return $rules;
    }

    public function datetimeAttributes(): array
    {
        $attributes = parent::datetimeAttributes();
        $attributes[] = 'availableFrom';
        $attributes[] = 'availableTo';

        return $attributes;
    }

    public function extraAttributes(): array
    {
        $names = parent::extraAttributes();
        $names[] = 'event';
        return $names;
    }

    public function getFieldLayout()
    {   
        if ($this->getType()) {
            return $this->getType()->getFieldLayout();
        }

        return null;
    }

    public function getEvent()
    {
        if ($this->_event !== null) {
            return $this->_event;
        }

        if ($this->eventId === null) {
            throw new InvalidConfigException('Ticket is missing its event');
        }

        $event = Event::find()
            ->id($this->eventId)
            ->siteId($this->siteId)
            ->anyStatus()
            ->trashed(null)
            ->one();

        if ($event === null) {
            throw new InvalidConfigException('Invalid event ID: ' . $this->eventId);
        }

        return $this->_event = $event;
    }

    public function setEvent(Event $event)
    {
        if ($event->siteId) {
            $this->siteId = $event->siteId;
        }

        if ($event->id) {
            $this->eventId = $event->id;
        }

        $this->_event = $event;
    }

    public function getType()
    {
        if ($this->typeId === null) {
            return null;
        }

        $ticketType = Events::getInstance()->getTicketTypes()->getTicketTypeById($this->typeId);

        if ($ticketType === null) {
            // throw new InvalidConfigException('Invalid ticket type ID: ' . $this->typeId);
        }

        return $ticketType;
    }

    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();

        return array_merge($labels, ['sku' => 'SKU']);
    }

    public function getIsEditable(): bool
    {
        /*$event = $this->getEvent();

        if ($event) {
            return $event->getIsEditable();
        }*/

        return false;
    }

    public function getCpEditUrl(): string
    {
        return $this->getEvent() ? $this->getEvent()->getCpEditUrl() : null;
    }

    public static function eagerLoadingMap(array $sourceElements, string $handle): array
    {
        if ($handle == 'event') {
            // Get the source element IDs
            $sourceElementIds = [];

            foreach ($sourceElements as $sourceElement) {
                $sourceElementIds[] = $sourceElement->id;
            }

            $map = (new Query())
                ->select('id as source, eventId as target')
                ->from('events_tickets')
                ->where(['in', 'id', $sourceElementIds])
                ->all();

            return [
                'elementType' => Event::class,
                'map' => $map,
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    public function setEagerLoadedElements(string $handle, array $elements)
    {
        if ($handle == 'event') {
            $event = $elements[0] ?? null;
            $this->setEvent($event);
        } else {
            parent::setEagerLoadedElements($handle, $elements);
        }
    }

    public function availableQuantity()
    {
        // Check if the event overall even has any more to buy - that's a hard-unavailable
        if ($this->event->getAvailableCapacity() < 1) {
            return false;
        }

        // If we've specifically not set a quantity on the ticket, treat it like unlimited
        if ($this->quantity === null) {
            return $this->event->getAvailableCapacity();
        }

        return $this->quantity;
    }

    public function getIsAvailable(): bool
    {
        if ($this->getStatus() !== Element::STATUS_ENABLED) {
            return false;
        }

        $currentTime = DateTimeHelper::currentTimeStamp();

        if ($this->availableFrom) {
            $availableFrom = $this->availableFrom->getTimestamp();

            if ($availableFrom > $currentTime) {
                return false;
            }
        }

        if ($this->availableTo) {
            $availableTo = $this->availableTo->getTimestamp();

            if ($availableTo < $currentTime) {
                return false;
            }
        }

        // Check if there are any tickets left
        if ($this->availableQuantity() < 1) {
            return false;
        }

        return true;
    }

    public function getStatus()
    {
        $status = parent::getStatus();

        $eventStatus = $this->getEvent()->getStatus();

        if ($eventStatus != Event::STATUS_LIVE) {
            return Element::STATUS_DISABLED;
        }

        return $status;
    }

    public function populateLineItem(LineItem $lineItem)
    {
        $errors = [];

        if ($lineItem->purchasable === $this) {
            $ticketCapacity = $lineItem->purchasable->quantity;
            $eventCapacity = $lineItem->purchasable->event->capacity;

            // If no ticket quantity provided, use the event's capacity
            if ($ticketCapacity === null) {
                $ticketCapacity = $eventCapacity;
            }

            // If no event capacity set (but a ticket quantity set), that's actually easy to process
            if ($eventCapacity === null) {
                $eventCapacity = $ticketCapacity;
            }

            // Just in case both are empty - then its an unlimited free-for-all!
            if ($ticketCapacity === null && $eventCapacity === null) {
                return;
            }

            $eventAvailable = $lineItem->purchasable->event->getAvailableCapacity();

            // Find the smallest number, out of the ticket or event capacity
            $availableTickets = min([$ticketCapacity, $eventAvailable]);

            // Sanity check for negative values thrown SQL errors
            if ($availableTickets < 1) {
                $availableTickets = 0;
            }

            if ($lineItem->qty > $availableTickets) {
                $lineItem->qty = $availableTickets;
                $errors[] = 'You reached the maximum ticket quantity for ' . $lineItem->purchasable->getDescription();
            }
        }

        if ($errors) {
            $cart = Commerce::getInstance()->getCarts()->getCart();
            $cart->addErrors($errors);

            Craft::$app->getSession()->setError(implode(',', $errors));
        }
    }

    public function afterOrderComplete(Order $order, LineItem $lineItem)
    {
        // Reduce quantity
        Craft::$app->getDb()->createCommand()->update('{{%events_tickets}}',
            ['quantity' => new Expression('quantity - :qty', [':qty' => $lineItem->qty])],
            ['id' => $this->id])->execute();

        // Update the quantity
        $this->quantity = (new Query())
            ->select(['quantity'])
            ->from('{{%events_tickets}}')
            ->where('id = :ticketId', [':ticketId' => $this->id])
            ->scalar();

        Craft::$app->getTemplateCaches()->deleteCachesByElementId($this->id);

        // Generate purchased tickets
        $elementsService = Craft::$app->getElements();

        for ($i = 0; $i < $lineItem->qty; $i++) {
            $purchasedTicket = new PurchasedTicket();
            $purchasedTicket->eventId = $this->eventId;
            $purchasedTicket->ticketId = $this->id;
            $purchasedTicket->orderId = $order->id;
            $purchasedTicket->lineItemId = $lineItem->id;
			$purchasedTicket->ticketSku = TicketHelper::generateTicketSKU();
			
            // Set the field values from the ticket (handle defaults, and values set on the ticket)
			$purchasedTicket->setFieldValues($this->getSerializedFieldValues());

            // But also allow overriding through the line item options
            foreach ($lineItem->options as $option => $value) {
                // Just catch any errors when trying to set attributes that aren't field handles
                try {
                    $purchasedTicket->setFieldValue($option, $value);
                } catch (\Throwable $e) {
                    continue;
                }
            }

            $elementsService->saveElement($purchasedTicket, false);
        }
    }

    public function getPurchasedTickets(LineItem $lineItem)
    {
        return PurchasedTicket::find()
            ->orderId($lineItem->order->id)
            ->lineItemId($lineItem->id)
            ->all();
    }

    public function getPurchasedTicketsForLineItem(LineItem $lineItem)
    {
        Craft::$app->getDeprecator()->log('Ticket::getPurchasedTicketsForLineItem(item)', 'item.purchasable.getPurchasedTicketsForLineItem(item) has been deprecated. Use item.purchasable.getPurchasedTickets(item) instead');

        return $this->getPurchasedTickets($lineItem);
    }


    // Purchasable
    // =========================================================================

    public function getPurchasableId(): int
    {
        return $this->id;
    }

    public function getSnapshot(): array
    {
        $data = [];
        $data['onSale'] = $this->getOnSale();
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
                foreach ($fieldLayout->getFields() as $field) {
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
                'fieldData' => []
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
            foreach ($fieldLayout->getFields() as $field) {
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

    public function getOnSale(): bool
    {
        return null === $this->salePrice ? false : (Currency::round($this->salePrice) != Currency::round($this->price));
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getDescription(): string
    {
        return (string)$this;
    }

    public function getTaxCategoryId(): int
    {
        return $this->getType()->taxCategoryId;
    }

    public function getShippingCategoryId(): int
    {
        return $this->getType()->shippingCategoryId;
    }

    public function getIsShippable(): bool
    {
        return false;
    }


    // Events
    // -------------------------------------------------------------------------

    public function afterSave(bool $isNew)
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
        $record->typeId = $this->typeId;
        $record->sku = $this->sku;
        $record->quantity = $this->quantity;
        $record->price = $this->price;
        $record->availableFrom = $this->availableFrom;
        $record->availableTo = $this->availableTo;
        $record->sortOrder = $this->sortOrder;

        $record->save(false);

        return parent::afterSave($isNew);
    }

    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        Craft::$app->getDb()->createCommand()
            ->update('{{%events_tickets}}', [
                'deletedWithEvent' => $this->deletedWithEvent,
            ], ['id' => $this->id], [], false)
            ->execute();

        return true;
    }

    public function beforeRestore(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        // Check to see if any other purchasable has the same SKU and update this one before restore
        $found = (new Query())->select(['[[p.sku]]', '[[e.id]]'])
            ->from('{{%commerce_purchasables}} p')
            ->leftJoin(Table::ELEMENTS . ' e', '[[p.id]]=[[e.id]]')
            ->where(['[[e.dateDeleted]]' => null, '[[p.sku]]' => $this->getSku()])
            ->andWhere(['not', ['[[e.id]]' => $this->getId()]])
            ->count();

        if ($found) {
            // Set new SKU in memory
            $this->sku = $this->getSku() . '-1';

            // Update ticket table with new SKU
            Craft::$app->getDb()->createCommand()->update('{{%events_tickets}}',
                ['sku' => $this->sku],
                ['id' => $this->getId()]
            )->execute();

            // Update purchasable table with new SKU
            Craft::$app->getDb()->createCommand()->update('{{%commerce_purchasables}}',
                ['sku' => $this->sku],
                ['id' => $this->getId()]
            )->execute();
        }

        return true;
    }


    // Protected methods
    // =========================================================================

    protected function tableAttributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'event': {
                return $this->event->title;
            }

            case 'price': {
                $code = Commerce::getInstance()->getPaymentCurrencies()->getPrimaryPaymentCurrencyIso();

                return Craft::$app->getLocale()->getFormatter()->asCurrency($this->$attribute, strtoupper($code));
            }

            default: {
                return parent::tableAttributeHtml($attribute);
            }
        }
    }

}
