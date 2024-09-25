<?php
namespace verbb\events\gql\queries;

use verbb\events\elements\Event;
use verbb\events\gql\arguments\EventArguments;
use verbb\events\gql\interfaces\EventInterface;
use verbb\events\gql\resolvers\EventResolver;
use verbb\events\helpers\Gql as GqlHelper;

use craft\gql\base\Query;

use GraphQL\Type\Definition\Type;

class EventQuery extends Query
{
    // Static Methods
    // =========================================================================

    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryEvents()) {
            return [];
        }

        return [
            'eventsEvents' => [
                'type' => Type::listOf(EventInterface::getType()),
                'args' => EventArguments::getArguments(),
                'resolve' => EventResolver::class . '::resolve',
                'description' => 'This query is used to query for events.',
            ],
            'eventsEvent' => [
                'type' => EventInterface::getType(),
                'args' => EventArguments::getArguments(),
                'resolve' => EventResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single event.',
            ],
            'eventsEventCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => EventArguments::getArguments(),
                'resolve' => EventResolver::class . '::resolveCount',
                'description' => 'This query is used to return the number of events.',
            ],
        ];
    }
}
