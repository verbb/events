<?php
namespace verbb\events\events;

use yii\base\Event;

class EventTypeEvent extends Event
{
    // Properties
    // =========================================================================

    public $eventType;
    public $isNew = false;
    
}
