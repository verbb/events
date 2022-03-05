<?php
namespace verbb\events\events;

use verbb\events\elements\Ticket;

use yii\base\Event;

class CustomizeTicketSnapshotDataEvent extends Event
{
    // Properties
    // =========================================================================

    public Ticket $ticket;
    public array $fieldData = [];
}
