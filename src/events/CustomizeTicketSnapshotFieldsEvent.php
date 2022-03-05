<?php
namespace verbb\events\events;

use verbb\events\elements\Ticket;

use yii\base\Event;

class CustomizeTicketSnapshotFieldsEvent extends Event
{
    // Properties
    // =========================================================================

    public Ticket $ticket;
    public array $fields = [];
}
