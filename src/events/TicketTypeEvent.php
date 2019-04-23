<?php
namespace verbb\events\events;

use yii\base\Event;

class TicketTypeEvent extends Event
{
    // Properties
    // =========================================================================

    public $ticketType;
    public $isNew = false;
    
}
