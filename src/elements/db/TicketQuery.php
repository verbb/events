<?php
namespace verbb\events\elements\db;

use verbb\events\elements\Event;
use verbb\events\elements\Session;
use verbb\events\elements\TicketType;
use verbb\events\elements\TicketCollection;

use craft\db\Table;
use craft\helpers\Db;

use yii\db\Connection;
use yii\db\Expression;

use craft\commerce\elements\db\PurchasableQuery;

class TicketQuery extends PurchasableQuery
{
    // Properties
    // =========================================================================

    public mixed $hasEvent = null;
    public mixed $ownerId = null;
    public mixed $eventId = null;
    public mixed $sessionId = null;
    public mixed $typeId = null;


    // Public Methods
    // =========================================================================

    public function event(mixed $value): static
    {
        if ($value instanceof Event) {
            $this->eventId = [$value->id];
        } else {
            $this->eventId = $value;
        }

        return $this;
    }

    public function eventId(mixed $value): static
    {
        $this->eventId = $value;
        return $this;
    }

    public function session(mixed $value): static
    {
        if ($value instanceof Session) {
            $this->sessionId = [$value->id];
        } else {
            $this->sessionId = $value;
        }

        return $this;
    }

    public function sessionId(mixed $value): static
    {
        $this->sessionId = $value;
        return $this;
    }

    public function type(mixed $value): static
    {
        if ($value instanceof TicketType) {
            $this->typeId = [$value->id];
        } else {
            $this->typeId = $value;
        }

        return $this;
    }

    public function typeId(mixed $value): static
    {
        $this->typeId = $value;
        return $this;
    }

    public function hasEvent(mixed $value): static
    {
        $this->hasEvent = $value;
        return $this;
    }

    public function collect(?Connection $db = null): TicketCollection
    {
        return TicketCollection::make(parent::collect($db));
    }


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('events_tickets');

        $this->query->select([
            'events_tickets.id',
            'events_tickets.eventId',
            'events_tickets.sessionId',
            'events_tickets.typeId',
        ]);

        if ($this->eventId) {
            $this->subQuery->andWhere(Db::parseParam('events_tickets.eventId', $this->eventId));
        }

        if ($this->sessionId) {
            $this->subQuery->andWhere(Db::parseParam('events_tickets.sessionId', $this->sessionId));
        }

        if ($this->typeId) {
            $this->subQuery->andWhere(Db::parseParam('events_tickets.typeId', $this->typeId));
        }

        // Apply the custom ordering for sessions + ticket types
        $this->_applySessionAndTypeJoins($this->query);
        $this->_applySessionAndTypeJoins($this->subQuery);
        
        // Order by the sortOrder values from the joined owners.
        $this->query->orderBy([
            'sessionOwners.sortOrder' => SORT_ASC,
            'typeOwners.sortOrder' => SORT_ASC,
        ]);

        return parent::beforePrepare();
    }

    protected function cacheTags(): array
    {
        $tags = [];

        if ($this->ownerId) {
            foreach ($this->ownerId as $ownerId) {
                $tags[] = "event:$ownerId";
            }
        }

        return $tags;
    }

    // Private Methods
    // =========================================================================

    private function _applySessionAndTypeJoins($query): void
    {
        // Join sessions pivot table.
        $query->leftJoin('{{%events_sessions}} sessions', '[[sessions.id]] = [[events_tickets.sessionId]]');

        // Normalize for querying
        $eventId = $this->eventId;

        if (!is_array($eventId)) {
            $eventId = [$eventId];
        }

        $params = [];

        $placeholders = [];

        foreach ($eventId as $i => $id) {
            $key = ':eventId' . $i;
            $placeholders[] = $key;
            $params[$key] = $id;
        }

        $idCondition = implode(', ', $placeholders);
        
        // Join elements_owners for sessions.
        $query->leftJoin(
            '{{%elements_owners}} sessionOwners',
            new Expression("[[sessionOwners.elementId]] = [[sessions.id]] AND [[sessionOwners.ownerId]] IN ($idCondition)")
        );
        
        // Join ticket types pivot table.
        $query->leftJoin('{{%events_ticket_types}} types', '[[types.id]] = [[events_tickets.typeId]]');
        
        // Join elements_owners for ticket types.
        $query->leftJoin(
            '{{%elements_owners}} typeOwners',
            new Expression("[[typeOwners.elementId]] = [[types.id]] AND [[typeOwners.ownerId]] IN ($idCondition)")
        );

        // Add the params to the query
        $query->addParams($params);
    }
}
