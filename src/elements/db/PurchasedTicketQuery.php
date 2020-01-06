<?php
namespace verbb\events\elements\db;

use Craft;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

use yii\db\Connection;

class PurchasedTicketQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public $eventId;
    public $ticketId;
    public $orderId;
    public $lineItemId;
    public $ticketSku;
    public $checkedIn;
    public $checkedInDate;

    protected $defaultOrderBy = ['events_purchasedtickets.dateCreated' => SORT_DESC];


    // Public Methods
    // =========================================================================

    public function eventId($value)
    {
        $this->eventId = $value;
        return $this;
    }

    public function ticketId($value)
    {
        $this->ticketId = $value;
        return $this;
    }

    public function orderId($value)
    {
        $this->orderId = $value;
        return $this;
    }

    public function lineItemId($value)
    {
        $this->lineItemId = $value;
        return $this;
    }

    public function ticketSku($value)
    {
        $this->ticketSku = $value;
        return $this;
    }

    public function checkedIn($value)
    {
        $this->checkedIn = $value;
        return $this;
    }

    public function checkedInDate($value)
    {
        $this->checkedInDate = $value;
        return $this;
    }


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('events_purchasedtickets');

        $this->query->select([
            'events_purchasedtickets.eventId',
            'events_purchasedtickets.ticketId',
            'events_purchasedtickets.orderId',
            'events_purchasedtickets.lineItemId',
            'events_purchasedtickets.ticketSku',
            'events_purchasedtickets.checkedIn',
            'events_purchasedtickets.checkedInDate',
            'events_purchasedtickets.dateCreated',
            'events_purchasedtickets.dateUpdated',
        ]);

        $this->addWhere('eventId', 'events_purchasedtickets.eventId');
        $this->addWhere('ticketId', 'events_purchasedtickets.ticketId');
        $this->addWhere('orderId', 'events_purchasedtickets.orderId');
        $this->addWhere('lineItemId', 'events_purchasedtickets.lineItemId');
        $this->addWhere('ticketSku', 'events_purchasedtickets.ticketSku');
        $this->addWhere('checkedIn', 'events_purchasedtickets.checkedIn');
        $this->addDateWhere('checkedInDate', 'events_purchasedtickets.checkedInDate');
        $this->addDateWhere('dateCreated', 'events_purchasedtickets.dateCreated');
        $this->addDateWhere('dateUpdated', 'events_purchasedtickets.dateUpdated');

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

    private function addDateWhere(string $property, string $column)
    {
        if ($this->{$property}) {
            $this->subQuery->andWhere(Db::parseDateParam($column, $this->{$property}));
        }
    }
}
