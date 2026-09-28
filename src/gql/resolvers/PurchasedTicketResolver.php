<?php
namespace verbb\events\gql\resolvers;

use verbb\events\elements\Event;
use verbb\events\elements\PurchasedTicket;
use verbb\events\helpers\Gql as GqlHelper;
use verbb\events\helpers\Table;

use craft\elements\db\ElementQuery;
use craft\elements\ElementCollection;
use craft\gql\base\ElementResolver;

class PurchasedTicketResolver extends ElementResolver
{
    // Static Methods
    // =========================================================================

    public static function prepareQuery(mixed $source, array $arguments, $fieldName = null): mixed
    {
        $allowedEventTypeIds = GqlHelper::getAllowedPurchasedTicketEventTypeIds();

        if ($allowedEventTypeIds === []) {
            return ElementCollection::empty();
        }

        if ($source instanceof Event && !in_array($source->typeId, $allowedEventTypeIds, true)) {
            return ElementCollection::empty();
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
