<?php
namespace verbb\events\gql\resolvers;

use verbb\events\elements\PurchasedTicket;
use verbb\events\helpers\Gql as GqlHelper;
use verbb\events\helpers\Table;

use craft\elements\db\ElementQuery;
use craft\gql\base\ElementResolver;

use Illuminate\Support\Collection;

class PurchasedTicketResolver extends ElementResolver
{
    // Static Methods
    // =========================================================================

    public static function prepareQuery(mixed $source, array $arguments, $fieldName = null): mixed
    {
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

        return $query;
    }
}
