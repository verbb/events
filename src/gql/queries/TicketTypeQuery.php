<?php
namespace verbb\events\gql\queries;

use verbb\events\elements\TicketType;
use verbb\events\gql\arguments\TicketTypeArguments;
use verbb\events\gql\interfaces\TicketTypeInterface;
use verbb\events\gql\resolvers\TicketTypeResolver;
use verbb\events\helpers\Gql as GqlHelper;

use craft\gql\base\Query;

use GraphQL\Type\Definition\Type;

class TicketTypeQuery extends Query
{
    // Static Methods
    // =========================================================================

    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryEvents()) {
            return [];
        }

        return [
            'eventsTicketTypes' => [
                'type' => Type::listOf(TicketTypeInterface::getType()),
                'args' => TicketTypeArguments::getArguments(),
                'resolve' => TicketTypeResolver::class . '::resolve',
                'description' => 'This query is used to query for ticket types.',
            ],
            'eventsTicketType' => [
                'type' => TicketTypeInterface::getType(),
                'args' => TicketTypeArguments::getArguments(),
                'resolve' => TicketTypeResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single ticket type.',
            ],
            'eventsTicketTypeCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => TicketTypeArguments::getArguments(),
                'resolve' => TicketTypeResolver::class . '::resolveCount',
                'description' => 'This query is used to return the number of ticket types.',
            ],
        ];
    }
}
