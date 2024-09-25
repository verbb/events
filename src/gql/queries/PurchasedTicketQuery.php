<?php
namespace verbb\events\gql\queries;

use verbb\events\elements\PurchasedTicket;
use verbb\events\gql\arguments\PurchasedTicketArguments;
use verbb\events\gql\interfaces\PurchasedTicketInterface;
use verbb\events\gql\resolvers\PurchasedTicketResolver;
use verbb\events\helpers\Gql as GqlHelper;

use craft\gql\base\Query;

use GraphQL\Type\Definition\Type;

class PurchasedTicketQuery extends Query
{
    // Static Methods
    // =========================================================================

    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryEvents()) {
            return [];
        }

        return [
            'eventsPurchasedTickets' => [
                'type' => Type::listOf(PurchasedTicketInterface::getType()),
                'args' => PurchasedTicketArguments::getArguments(),
                'resolve' => PurchasedTicketResolver::class . '::resolve',
                'description' => 'This query is used to query for purchased tickets.',
            ],
            'eventsPurchasedTicket' => [
                'type' => PurchasedTicketInterface::getType(),
                'args' => PurchasedTicketArguments::getArguments(),
                'resolve' => PurchasedTicketResolver::class . '::resolveOne',
                'description' => 'This query is used to query for a single purchased ticket.',
            ],
            'eventsPurchasedTicketCount' => [
                'type' => Type::nonNull(Type::int()),
                'args' => PurchasedTicketArguments::getArguments(),
                'resolve' => PurchasedTicketResolver::class . '::resolveCount',
                'description' => 'This query is used to return the number of purchased tickets.',
            ],
        ];
    }
}
