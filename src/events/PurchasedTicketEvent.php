<?php
namespace verbb\events\events;

use verbb\events\elements\PurchasedTicket;

use craft\events\CancelableEvent;

class PurchasedTicketEvent extends CancelableEvent
{
    // Properties
    // =========================================================================

    public PurchasedTicket $purchasedTicket;

}
