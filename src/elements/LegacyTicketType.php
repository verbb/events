<?php
namespace verbb\events\elements;

use verbb\events\elements\db\LegacyTicketTypeQuery;

use craft\base\Element;

class LegacyTicketType extends Element
{
    // Static Methods
    // =========================================================================

    public static function find(): LegacyTicketTypeQuery
    {
        return new LegacyTicketTypeQuery(static::class);
    }

}
