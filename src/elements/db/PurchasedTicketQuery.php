<?php
namespace verbb\events\elements\db;

use verbb\events\elements\Event;
use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\Session;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;
use verbb\events\elements\PurchasedTicketCollection;
use verbb\events\helpers\ParentElements;

use Craft;
use craft\db\Query;
use craft\elements\User;
use craft\elements\db\ElementQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;

use yii\db\Connection;

use craft\commerce\db\Table as CommerceTable;

class PurchasedTicketQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public mixed $eventId = null;
    public mixed $eventTypeId = null;
    public mixed $sessionId = null;
    public mixed $ticketId = null;
    public mixed $ticketTypeId = null;
    public mixed $orderId = null;
    public mixed $lineItemId = null;
    public mixed $checkedIn = null;
    public mixed $checkedInDate = null;
    public mixed $reservationStatus = null;

    public mixed $customerId = null;

    protected array $defaultOrderBy = ['events_purchased_tickets.dateCreated' => SORT_DESC];


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

    public function eventTypeId($value): static
    {
        $this->eventTypeId = $value;

        return $this;
    }

    public function event(mixed $value): static
    {
        if ($value instanceof Event) {
            $this->eventId = [$value->id];
        } else {
            $this->eventId = $value;
        }

        return $this;
    }

    public function sessionId($value): static
    {
        $this->sessionId = $value;

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

    public function ticketId($value): static
    {
        $this->ticketId = $value;

        return $this;
    }

    public function ticket(mixed $value): static
    {
        if ($value instanceof Ticket) {
            $this->ticketId = [$value->id];
        } else {
            $this->ticketId = $value;
        }

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

    public function reservationStatus($value): static
    {
        $this->reservationStatus = $value;

        return $this;
    }

    public function customer(User $value = null): static
    {
        $this->customerId = $value?->id;

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
        } elseif ($value !== null) {
            $this->ticketTypeId = (new Query())
                ->select(['id'])
                ->from(['{{%events_ticket_types}}'])
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

    public function collect(?Connection $db = null): PurchasedTicketCollection
    {
        return PurchasedTicketCollection::make(parent::collect($db));
    }

    public function populate($rows): array
    {
        if ($this->asArray || !$rows) {
            return parent::populate($rows);
        }

        // Each purchased ticket resolves its event, ticket and ticket type in `init()` for its title and field layout,
        // so load them once for all rows. Tickets go first, because the parents they load cover most of the rest.
        $tickets = ParentElements::load([Ticket::class => array_column($rows, 'ticketId')])[Ticket::class];
        $known = [];

        foreach ($tickets as $ticket) {
            if ($ticket?->eventId && ($event = $ticket->getEvent())) {
                $known[Event::class][$event->id] = $event;
            }

            if ($ticket?->sessionId && ($session = $ticket->getSession())) {
                $known[Session::class][$session->id] = $session;
            }

            if ($ticket?->typeId && ($type = $ticket->getType())) {
                $known[TicketType::class][$type->id] = $type;
            }
        }

        $parents = ParentElements::load([
            Event::class => array_column($rows, 'eventId'),
            Session::class => array_column($rows, 'sessionId'),
            TicketType::class => array_column($rows, 'ticketTypeId'),
        ], $known);

        $parents[Ticket::class] = $tickets;

        $elements = ParentElements::populate($parents, fn() => parent::populate($rows));

        // `init()` doesn't always need every parent (the session in particular), so hand the loaded ones over for later
        // getter calls, as eager loading would.
        foreach ($elements as $element) {
            if (!$element instanceof PurchasedTicket) {
                continue;
            }

            if ($event = $parents[Event::class][$element->eventId] ?? null) {
                $element->setEvent($event);
            }

            if ($session = $parents[Session::class][$element->sessionId] ?? null) {
                $element->setSession($session);
            }

            if ($ticket = $parents[Ticket::class][$element->ticketId] ?? null) {
                $element->setTicket($ticket);
            }

            if ($ticketType = $parents[TicketType::class][$element->ticketTypeId] ?? null) {
                $element->setTicketType($ticketType);
            }
        }

        return $elements;
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

        $this->joinElementTable('events_purchased_tickets');

        $this->query->select([
            'events_purchased_tickets.eventId',
            'events_purchased_tickets.sessionId',
            'events_purchased_tickets.ticketId',
            'events_purchased_tickets.ticketTypeId',
            'events_purchased_tickets.orderId',
            'events_purchased_tickets.lineItemId',
            'events_purchased_tickets.checkedIn',
            'events_purchased_tickets.checkedInDate',
            'events_purchased_tickets.reservationStatus',
            'events_purchased_tickets.cancelledAt',
            'events_purchased_tickets.cancelledReason',
            'events_purchased_tickets.cancelledById',
            'events_purchased_tickets.dateCreated',
            'events_purchased_tickets.dateUpdated',
        ]);

        if (isset($this->eventId)) {
            $this->subQuery->andWhere(Db::parseParam('events_purchased_tickets.eventId', $this->eventId));
        }

        if (isset($this->eventTypeId)) {
            $this->subQuery->innerJoin('{{%events_events}} events_events', '[[events_events.id]] = [[events_purchased_tickets.eventId]]');
            $this->subQuery->andWhere(['events_events.typeId' => $this->eventTypeId]);
        }

        if (isset($this->sessionId)) {
            $this->subQuery->andWhere(Db::parseParam('events_purchased_tickets.sessionId', $this->sessionId));
        }

        if (isset($this->ticketId)) {
            $this->subQuery->andWhere(Db::parseParam('events_purchased_tickets.ticketId', $this->ticketId));
        }

        if (isset($this->ticketTypeId)) {
            $this->subQuery->andWhere(Db::parseParam('events_purchased_tickets.ticketTypeId', $this->ticketTypeId));
        }

        if (isset($this->orderId)) {
            $this->subQuery->andWhere(Db::parseParam('events_purchased_tickets.orderId', $this->orderId));
        }

        if (isset($this->ticketId)) {
            $this->subQuery->andWhere(Db::parseParam('events_purchased_tickets.ticketId', $this->ticketId));
        }

        if (isset($this->orderId)) {
            $this->subQuery->andWhere(Db::parseParam('events_purchased_tickets.orderId', $this->orderId));
        }

        if (isset($this->lineItemId)) {
            $this->subQuery->andWhere(Db::parseParam('events_purchased_tickets.lineItemId', $this->lineItemId));
        }

        if (isset($this->checkedIn)) {
            $this->subQuery->andWhere(Db::parseParam('events_purchased_tickets.checkedIn', $this->checkedIn));
        }

        if (isset($this->checkedInDate)) {
            $this->subQuery->andWhere(Db::parseDateParam('events_purchased_tickets.checkedInDate', $this->checkedInDate));
        }

        if (isset($this->reservationStatus)) {
            $this->subQuery->andWhere(Db::parseParam('events_purchased_tickets.reservationStatus', $this->reservationStatus));
        }

        if ($this->customerId) {
            $this->subQuery->innerJoin(CommerceTable::ORDERS . ' orders', '[[orders.id]] = [[events_purchased_tickets.orderId]]');
            $this->subQuery->andWhere(['=', '[[orders.customerId]]', $this->customerId]);
            $this->subQuery->andWhere(['=', '[[orders.isCompleted]]', true]);
        }

        if (isset($this->ticketTypeId)) {
            $this->subQuery->innerJoin('{{%events_tickets}} events_tickets', '[[events_tickets.id]] = [[events_purchased_tickets.ticketId]]');
            $this->subQuery->andWhere(['[[events_tickets.typeId]]' => $this->ticketTypeId]);
        }

        return parent::beforePrepare();
    }


    // Protected Methods
    // =========================================================================

    protected function fieldLayouts(): array
    {
        // Ensure element queries know that we use another element's layout
        return Craft::$app->getFields()->getLayoutsByType(TicketType::class);
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
        } elseif (is_numeric($this->ticketTypeId)) {
            $this->ticketTypeId = [$this->ticketTypeId];
        } elseif (!is_array($this->ticketTypeId) || !ArrayHelper::isNumeric($this->ticketTypeId)) {
            $this->ticketTypeId = (new Query())
                ->select(['id'])
                ->from(['{{%events_ticket_types}}'])
                ->where(Db::parseParam('id', $this->ticketTypeId))
                ->column();
        }
    }
}
