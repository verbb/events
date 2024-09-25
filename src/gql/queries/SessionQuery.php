<?php
namespace verbb\events\gql\queries;

use verbb\events\elements\Session;
use verbb\events\gql\arguments\SessionArguments;
use verbb\events\gql\interfaces\SessionInterface;
use verbb\events\gql\resolvers\SessionResolver;
use verbb\events\helpers\Gql as GqlHelper;

use craft\gql\base\Query;

use GraphQL\Type\Definition\Type;

class SessionQuery extends Query
{
    // Static Methods
    // =========================================================================

    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryEvents()) {
            return [];
        }
        
        return [
            'eventsSessions' => [
                'type' => Type::listOf(SessionInterface::getType()),
                'args' => SessionArguments::getArguments(),
                'resolve' => SessionResolver::class . '::resolve',
                'description' => 'This query is used to query for sessions.',
            ],
            'eventsSession' => [
                'type' => SessionInterface::getType(),
                'args' => SessionArguments::getArguments(),
                'resolve' => SessionResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single session.',
            ],
            'eventsSessionCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => SessionArguments::getArguments(),
                'resolve' => SessionResolver::class . '::resolveCount',
                'description' => 'This query is used to return the number of sessions.',
            ],
        ];
    }
}
