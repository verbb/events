<?php
namespace verbb\events\elements;

use Craft;
use craft\elements\ElementCollection;

class TicketTypeCollection extends ElementCollection
{
    // Static Methods
    // =========================================================================

    public static function make($items = [])
    {
        foreach ($items as &$item) {
            if ($item instanceof TicketType) {
                continue;
            }

            $item = Craft::createObject(TicketType::class, [
                'config' => ['attributes' => $item],
            ]);
        }

        return parent::make($items);
    }
}
