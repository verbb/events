<?php
namespace verbb\events\events;

use yii\base\Event;

class CustomizeEventSnapshotFieldsEvent extends Event
{
    // Properties
    // =========================================================================

    public $event;
    public $fields;
}
