<?php
namespace verbb\events\gql\resolvers;

use verbb\events\elements\Ticket;
use verbb\events\helpers\Gql as GqlHelper;
use verbb\events\helpers\Table;

use craft\elements\db\ElementQuery;
use craft\elements\ElementCollection;
use craft\gql\base\ElementResolver;
use craft\helpers\Db;

class TicketResolver extends ElementResolver
{
    // Static Methods
    // =========================================================================

    public static function prepareQuery(mixed $source, array $arguments, $fieldName = null): mixed
    {
        if ($source === null) {
            $query = Ticket::find();
        } else {
            $query = $source->$fieldName;
        }

        if (!$query instanceof ElementQuery) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        if (!GqlHelper::canQueryEvents()) {
            return ElementCollection::empty();
        }

        return $query;
    }
}
