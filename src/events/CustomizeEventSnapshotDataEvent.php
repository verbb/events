<?php
namespace verbb\events\events;

use verbb\events\elements\Event as EventElement;

use yii\base\Event;

class CustomizeEventSnapshotDataEvent extends Event
{
    // Properties
    // =========================================================================

    public EventElement $event;
    public array $fieldData = [];
}
