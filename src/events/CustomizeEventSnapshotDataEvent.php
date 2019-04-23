<?php
namespace verbb\events\events;

use yii\base\Event;

class CustomizeEventSnapshotDataEvent extends Event
{
    // Properties
    // =========================================================================

    public $event;
    public $fieldData;
}
