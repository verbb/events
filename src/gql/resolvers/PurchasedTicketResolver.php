<?php
namespace verbb\events\gql\resolvers;

use verbb\events\elements\Event;
use verbb\events\elements\PurchasedTicket;
use verbb\events\helpers\Gql as GqlHelper;

use craft\elements\db\ElementQuery;
use craft\gql\base\ElementResolver;

class PurchasedTicketResolver extends ElementResolver
{
    // Static Methods
    // =========================================================================

    public static function prepareQuery(mixed $source, array $arguments, $fieldName = null): mixed
    {
        // UID criteria and ordering disclose identifier information even when UID output is hidden.
        $requiresCheckIn = isset($arguments['uid']) || preg_match('/\buid\b/i', $arguments['orderBy'] ?? '') === 1;
        $allowedEventTypeIds = GqlHelper::getAllowedPurchasedTicketEventTypeIds($requiresCheckIn);

        if ($allowedEventTypeIds === []) {
            return PurchasedTicket::find()->andWhere('0=1');
        }

        if ($source instanceof Event && !in_array($source->typeId, $allowedEventTypeIds, true)) {
            return PurchasedTicket::find()->andWhere('0=1');
        }

        if ($source === null) {
            $query = PurchasedTicket::find();
        } else {
            $query = $source->$fieldName;
        }

        if (!$query instanceof ElementQuery) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        $query->eventTypeId($allowedEventTypeIds);

        return $query;
    }
}
