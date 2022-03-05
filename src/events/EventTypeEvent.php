<?php
namespace verbb\events\events;

use verbb\events\models\EventType;

use yii\base\Event;

class EventTypeEvent extends Event
{
    // Properties
    // =========================================================================

    public EventType $eventType;
    public bool $isNew = false;

}
