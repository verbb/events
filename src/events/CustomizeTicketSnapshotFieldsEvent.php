<?php
namespace verbb\events\events;

use yii\base\Event;

class CustomizeTicketSnapshotFieldsEvent extends Event
{
    // Properties
    // =========================================================================

    public $ticket;
    public $fields;
}
