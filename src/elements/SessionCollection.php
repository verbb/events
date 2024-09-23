<?php
namespace verbb\events\elements;

use Craft;
use craft\elements\ElementCollection;

class SessionCollection extends ElementCollection
{
    // Static Methods
    // =========================================================================
    
    public static function make($items = [])
    {
        foreach ($items as &$item) {
            if ($item instanceof Session) {
                continue;
            }

            $item = Craft::createObject(Session::class, [
                'config' => ['attributes' => $item],
            ]);
        }

        return parent::make($items);
    }
}
