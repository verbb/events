<?php
namespace verbb\events\events;

use verbb\events\elements\Event as EventElement;

use yii\base\Event;

class CustomizeEventSnapshotFieldsEvent extends Event
{
    // Properties
    // =========================================================================

    public EventElement $event;
    public array $fields = [];
}
