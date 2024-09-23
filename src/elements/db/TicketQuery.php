<?php
namespace verbb\events\elements\db;

use verbb\events\elements\Event;
use verbb\events\elements\Session;
use verbb\events\elements\TicketType;
use verbb\events\elements\TicketCollection;

use craft\helpers\Db;

use yii\db\Connection;

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

    protected array $defaultOrderBy = ['events_tickets.id' => SORT_ASC];


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
}
