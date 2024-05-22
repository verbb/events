<?php
namespace verbb\events\events;

use verbb\events\elements\PurchasedTicket;

use yii\base\Event;

class PurchasedTicketEvent extends Event
{
    // Properties
    // =========================================================================

    public PurchasedTicket $purchasedTicket;

}
