<?php
namespace verbb\events\elements\traits;

use verbb\events\elements\Event;
use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\PurchasedTicketCollection;
use verbb\events\elements\Session;
use verbb\events\elements\TicketType;
use verbb\events\elements\db\PurchasedTicketQuery;

use craft\base\ElementInterface;
use craft\elements\NestedElementManager;
use craft\enums\PropagationMethod;

trait PurchasedTicketTrait
{
    // Properties
    // =========================================================================

    private ?PurchasedTicketCollection $_purchasedTickets = null;
    private ?NestedElementManager $_purchasedTicketManager = null;


    // Public Methods
    // =========================================================================

    public function getPurchasedTickets(bool $includeDisabled = false): PurchasedTicketCollection
    {
        if (!isset($this->_purchasedTickets)) {
            if (!$this->id) {
                return PurchasedTicketCollection::make();
            }

            $this->_purchasedTickets = self::createPurchasedTicketQuery($this)->status(null)->collect();
        }

        return $this->_purchasedTickets->filter(fn(PurchasedTicket $purchasedTicket) => $includeDisabled || $purchasedTicket->enabled);
    }

    public function setPurchasedTickets(PurchasedTicketCollection|PurchasedTicketQuery|array $purchasedTickets): void
    {
        if ($purchasedTickets instanceof PurchasedTicketQuery) {
            $this->_purchasedTickets = null;
            return;
        }

        $this->_purchasedTickets = $purchasedTickets instanceof PurchasedTicketCollection ? $purchasedTickets : PurchasedTicketCollection::make($purchasedTickets);
    }

    public function getPurchasedTicketManager(ElementInterface $element): NestedElementManager
    {
        // Handle this trait being used in a few different elements
        if (!isset($this->_purchasedTicketManager)) {
            $params = [
                'attribute' => 'purchasedTickets',
                'propagationMethod' => PropagationMethod::All,
                'valueGetter' => fn() => $this->getPurchasedTickets(true),
            ];

            if ($element instanceof Event) {
                $query = fn(ElementInterface $owner) => self::createPurchasedTicketTypeQuery($owner)->eventId($element->id);

                $params['ownerIdParam'] = 'eventId';
                $params['primaryOwnerIdParam'] = 'eventId';
            } else if ($element instanceof Session) {
                $query = fn(ElementInterface $owner) => self::createPurchasedTicketTypeQuery($owner)->sessionId($element->id);

                $params['ownerIdParam'] = 'sessionId';
                $params['primaryOwnerIdParam'] = 'sessionId';
            } else if ($element instanceof TicketType) {
                $query = fn(ElementInterface $owner) => self::createPurchasedTicketTypeQuery($owner)->ticketTypeId($element->id);

                $params['ownerIdParam'] = 'ticketTypeId';
                $params['primaryOwnerIdParam'] = 'ticketTypeId';
            }

            $this->_purchasedTicketManager = new NestedElementManager(PurchasedTicket::class, $query, $params);
        }

        return $this->_purchasedTicketManager;
    }


    // Private Methods
    // =========================================================================

    private static function createPurchasedTicketTypeQuery(ElementInterface $element): PurchasedTicketTypeQuery
    {
        return PurchasedTicket::find()
            ->siteId($element->siteId)
            ->orderBy(['sortOrder' => SORT_ASC]);
    }
}