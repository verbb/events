<?php
namespace verbb\events\events;

use verbb\events\elements\TicketType;

use yii\base\Event;

class TicketTypeEvent extends Event
{
    // Properties
    // =========================================================================

    public TicketType $ticketType;
    public bool $isNew = false;
    
}
