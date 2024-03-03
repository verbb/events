<?php
namespace verbb\events\fields;

use verbb\events\elements\Event;

use Craft;
use craft\fields\BaseRelationField;

class Events extends BaseRelationField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('events', 'Events');
    }

    public static function icon(): string
    {
        return '@verbb/events/icon-mask.svg';
    }

    public static function elementType(): string
    {
        return Event::class;
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('events', 'Add an event');
    }
}
