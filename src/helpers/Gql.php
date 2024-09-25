<?php
namespace verbb\events\helpers;

use craft\helpers\Gql as GqlHelper;

class Gql extends GqlHelper
{
    // Static Methods
    // =========================================================================

    public static function canQueryEvents(): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema();

        return isset($allowedEntities['eventsEventTypes']);
    }
}