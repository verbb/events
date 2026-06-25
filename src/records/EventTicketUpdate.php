<?php
namespace verbb\events\records;

use craft\db\ActiveRecord;

class EventTicketUpdate extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%events_ticket_updates}}';
    }
}
