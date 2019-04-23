<?php
namespace verbb\events\events;

use yii\base\Event;

class CustomizeTicketSnapshotDataEvent extends Event
{
    // Properties
    // =========================================================================

    public $ticket;
    public $fieldData;
}
