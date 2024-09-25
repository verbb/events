<?php
namespace verbb\events\gql\queries;

use verbb\events\elements\Ticket;
use verbb\events\gql\arguments\TicketArguments;
use verbb\events\gql\interfaces\TicketInterface;
use verbb\events\gql\resolvers\TicketResolver;
use verbb\events\helpers\Gql as GqlHelper;

use craft\gql\base\Query;

use GraphQL\Type\Definition\Type;

class TicketQuery extends Query
{
    // Static Methods
    // =========================================================================

    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryEvents()) {
            return [];
        }

        return [
            'eventsTickets' => [
                'type' => Type::listOf(TicketInterface::getType()),
                'args' => TicketArguments::getArguments(),
                'resolve' => TicketResolver::class . '::resolve',
                'description' => 'This query is used to query for tickets.',
            ],
            'eventsTicket' => [
                'type' => TicketInterface::getType(),
                'args' => TicketArguments::getArguments(),
                'resolve' => TicketResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single ticket.',
            ],
            'eventsTicketCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => TicketArguments::getArguments(),
                'resolve' => TicketResolver::class . '::resolveCount',
                'description' => 'This query is used to return the number of tickets.',
            ],
        ];
    }
}
