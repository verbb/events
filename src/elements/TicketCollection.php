<?php
namespace verbb\events\elements;

use Craft;
use craft\elements\ElementCollection;

class TicketCollection extends ElementCollection
{
    // Static Methods
    // =========================================================================

    public static function make($items = [])
    {
        foreach ($items as &$item) {
            if ($item instanceof Ticket) {
                continue;
            }

            $item = Craft::createObject(Ticket::class, [
                'config' => ['attributes' => $item],
            ]);
        }

        return parent::make($items);
    }
}
