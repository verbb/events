<?php
namespace verbb\events\elements;

use verbb\events\elements\db\LegacyTicketQuery;

use craft\base\Element;

class LegacyTicket extends Element
{
    // Static Methods
    // =========================================================================

    public static function find(): LegacyTicketQuery
    {
        return new LegacyTicketQuery(static::class);
    }

}
