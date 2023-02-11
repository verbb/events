<?php
namespace verbb\events\elements\db;

use verbb\events\elements\TicketType;

use craft\db\Query;
use craft\elements\User;
use craft\elements\db\ElementQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;

use craft\commerce\db\Table as CommerceTable;

class PurchasedTicketQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public mixed $eventId = null;
    public mixed $ticketId = null;
    public mixed $orderId = null;
    public mixed $lineItemId = null;
    public mixed $ticketSku = null;
    public mixed $checkedIn = null;
    public mixed $checkedInDate = null;

    public mixed $customerId = null;
    public mixed $ticketTypeId = null;

    protected array $defaultOrderBy = ['events_purchasedtickets.dateCreated' => SORT_DESC];


    // Public Methods
    // =========================================================================

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

    public function eventId($value): static
    {
        $this->eventId = $value;
        return $this;
    }

    public function ticketId($value): static
    {
        $this->ticketId = $value;
        return $this;
    }

    public function orderId($value): static
    {
        $this->orderId = $value;
        return $this;
    }

    public function lineItemId($value): static
    {
        $this->lineItemId = $value;
        return $this;
    }

    public function ticketSku($value): static
    {
        $this->ticketSku = $value;
        return $this;
    }

    public function checkedIn($value): static
    {
        $this->checkedIn = $value;
        return $this;
    }

    public function checkedInDate($value): static
    {
        $this->checkedInDate = $value;
        return $this;
    }

    public function customer(User $value = null): static
    {
        if ($value) {
            $this->customerId = $value->id;
        } else {
            $this->customerId = null;
        }

        return $this;
    }

    public function customerId($value): static
    {
        $this->customerId = $value;
        return $this;
    }

    public function ticketType($value): static
    {
        if ($value instanceof TicketType) {
            $this->ticketTypeId = [$value->id];
        } else if ($value !== null) {
            $this->ticketTypeId = (new Query())
                ->select(['id'])
                ->from(['{{%events_tickettypes}}'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        } else {
            $this->ticketTypeId = null;
        }

        return $this;
    }

    public function ticketTypeId($value): static
    {
        $this->ticketTypeId = $value;
        return $this;
    }


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        $this->_normalizeTicketTypeId();

        // See if 'ticketType' were set to invalid handles
        if ($this->ticketTypeId === []) {
            return false;
        }

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

        if ($this->customerId) {
            $this->subQuery->innerJoin(CommerceTable::ORDERS . ' orders', '[[orders.id]] = [[events_purchasedtickets.orderId]]');
            $this->subQuery->andWhere(['=', '[[orders.customerId]]', $this->customerId]);
            $this->subQuery->andWhere(['=', '[[orders.isCompleted]]', true]);
        }

        if (isset($this->ticketTypeId)) {
            $this->subQuery->innerJoin('{{%events_tickets}} events_tickets', '[[events_tickets.id]] = [[events_purchasedtickets.ticketId]]');
            $this->subQuery->andWhere(['[[events_tickets.typeId]]' => $this->ticketTypeId]);
        }

        return parent::beforePrepare();
    }


    // Private Methods
    // =========================================================================

    /**
     * Normalizes the ticketTypeId param to an array of IDs or null
     */
    private function _normalizeTicketTypeId(): void
    {
        if (empty($this->ticketTypeId)) {
            $this->ticketTypeId = null;
        } else if (is_numeric($this->ticketTypeId)) {
            $this->ticketTypeId = [$this->ticketTypeId];
        } else if (!is_array($this->ticketTypeId) || !ArrayHelper::isNumeric($this->ticketTypeId)) {
            $this->ticketTypeId = (new Query())
                ->select(['id'])
                ->from(['{{%events_tickettypes}}'])
                ->where(Db::parseParam('id', $this->ticketTypeId))
                ->column();
        }
    }

    private function addWhere(string $property, string $column): void
    {
        if ($this->{$property}) {
            $this->subQuery->andWhere(Db::parseParam($column, $this->{$property}));
        }
    }

    private function addDateWhere(string $property, string $column): void
    {
        if ($this->{$property}) {
            $this->subQuery->andWhere(Db::parseDateParam($column, $this->{$property}));
        }
    }
}
