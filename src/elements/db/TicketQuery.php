<?php
namespace verbb\events\elements\db;

use Craft;
use craft\base\Element;
use craft\db\Query;
use craft\db\Table;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

use craft\commerce\db\Table as CommerceTable;
use craft\commerce\models\Customer;

use yii\db\Connection;

class TicketQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public $eventId;
    public $typeId;
    public $sku;
    public $quantity;
    public $price;
    public $availableFrom;
    public $availableTo;

    public $editable = false;
    public $event;
    public $hasSales;
    public $hasEvent;
    public $customerId;

    protected $defaultOrderBy = ['events_tickets.sortOrder' => SORT_ASC];


    // Public Methods
    // =========================================================================

    public function __construct($elementType, array $config = [])
    {
        // Default status
        if (!isset($config['status'])) {
            $config['status'] = Element::STATUS_ENABLED;
        }

        parent::__construct($elementType, $config);
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'customer':
                $this->customer($value);
                break;
            default:
                parent::__set($name, $value);
        }
    }

    public function sku($value)
    {
        $this->sku = $value;
        return $this;
    }

    public function event($value)
    {
        $this->event = $value;
        return $this;
    }

    public function eventId($value)
    {
        $this->eventId = $value;
        return $this;
    }

    public function typeId($value)
    {
        $this->typeId = $value;
        return $this;
    }

    public function price($value)
    {
        $this->price = $value;
        return $this;
    }

    public function quantity($value)
    {
        $this->quantity = $value;
        return $this;
    }

    public function hasEvent($value)
    {
        $this->hasEvent = $value;
        return $this;
    }

    public function availableFrom($value)
    {
        $this->availableFrom = $value;
        return $this;
    }

    public function availableTo($value)
    {
        $this->availableTo = $value;
        return $this;
    }

    public function customer(Customer $value = null)
    {
        if ($value) {
            $this->customerId = $value->id;
        } else {
            $this->customerId = null;
        }

        return $this;
    }

    public function customerId($value)
    {
        $this->customerId = $value;
        return $this;
    }


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('events_tickets');
            
        $this->query->select([
            'events_tickets.id',
            'events_tickets.eventId',
            'events_tickets.typeId',
            'events_tickets.sortOrder',
            'events_tickets.sku',
            'events_tickets.quantity',
            'events_tickets.price',
            'events_tickets.availableFrom',
            'events_tickets.availableTo',
        ]);

        if ($this->event) {
            if ($this->event instanceof Event) {
                $this->eventId = $this->event->id;
            } else {
                $this->addWhere('event', 'events_tickets.eventId');
            }
        }

        $this->addWhere('id', 'events_tickets.id');
        $this->addWhere('eventId', 'events_tickets.eventId');
        $this->addWhere('typeId', 'events_tickets.typeId');
        $this->addWhere('sku', 'events_tickets.sku');
        $this->addWhere('quantity', 'events_tickets.quantity');
        $this->addWhere('price', 'events_tickets.price');
        $this->addWhere('availableFrom', 'events_tickets.availableFrom');
        $this->addWhere('availableTo', 'events_tickets.availableTo');

        if ($this->customerId) {
            $this->subQuery->innerJoin(CommerceTable::LINEITEMS . ' lineitems', '[[events_tickets.id]] = [[lineitems.purchasableId]]');
            $this->subQuery->innerJoin(CommerceTable::ORDERS . ' orders', '[[lineitems.orderId]] = [[orders.id]]');
            $this->subQuery->andWhere(['=', '[[orders.customerId]]', $this->customerId]);
            $this->subQuery->andWhere(['=', '[[orders.isCompleted]]', true]);
            $this->subQuery->groupBy(['events_tickets.id']);
        }

        $this->_applyHasEventParam();

        return parent::beforePrepare();
    }


    // Private Methods
    // =========================================================================

    private function addWhere(string $property, string $column)
    {
        if ($this->{$property}) {
            $this->subQuery->andWhere(Db::parseParam($column, $this->{$property}));
        }
    }

    private function _applyHasEventParam()
    {
        if ($this->hasEvent) {
            if ($this->hasEvent instanceof EventQuery) {
                $eventQuery = $this->hasEvent;
            } else {
                $query = Event::find();
                $eventQuery = Craft::configure($query, $this->hasEvent);
            }

            $eventQuery->limit = null;
            $eventQuery->select('events_events.id');
            $eventIds = $eventQuery->column();

            // Remove any blank event IDs (if any)
            $eventIds = array_filter($eventIds);

            $this->subQuery->andWhere(['in', 'events_events.id', $eventIds]);
        }
    }
}
