<?php
namespace verbb\events\helpers;

use craft\helpers\Db;
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

    public static function canQueryPurchasedTickets(): bool
    {
        return self::getAllowedPurchasedTicketEventTypeIds() !== [];
    }

    public static function getAllowedPurchasedTicketEventTypeIds(): array
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema();
        $eventTypeUids = $allowedEntities['eventsEventTypes'] ?? [];
        $purchasedTicketEventTypeUids = $allowedEntities['eventsPurchasedTickets'] ?? [];

        if (!is_array($eventTypeUids) || !is_array($purchasedTicketEventTypeUids)) {
            return [];
        }

        $allowedUids = array_intersect($eventTypeUids, $purchasedTicketEventTypeUids);

        return array_values(Db::idsByUids('{{%events_event_types}}', $allowedUids));
    }
}
