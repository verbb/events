<?php
namespace verbb\events\elements;

use Craft;
use craft\elements\ElementCollection;

class PurchasedTicketCollection extends ElementCollection
{
    // Static Methods
    // =========================================================================

    public static function make($items = [])
    {
        foreach ($items as &$item) {
            if ($item instanceof PurchasedTicket) {
                continue;
            }

            $item = Craft::createObject(PurchasedTicket::class, [
                'config' => ['attributes' => $item],
            ]);
        }

        return parent::make($items);
    }
}
