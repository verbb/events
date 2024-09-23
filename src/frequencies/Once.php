<?php
namespace verbb\events\frequencies;

use verbb\events\base\Frequency;

use Craft;

class Once extends Frequency
{
    // Static Methods
    // =========================================================================

    public static function id(): string
    {
        return 'once';
    }

    public static function displayName(): string
    {
        return Craft::t('events', 'Once');
    }
}