<?php
namespace verbb\events\gql\arguments;

use craft\gql\base\ElementArguments;
use craft\gql\types\DateTime;
use craft\gql\types\QueryArgument;

use GraphQL\Type\Definition\Type;

class TicketArguments extends ElementArguments
{
    // Static Methods
    // =========================================================================

    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'eventId' => [
                'name' => 'eventId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the ticket’s event ID.',
            ],
            'sessionId' => [
                'name' => 'sessionId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the ticket’s session ID.',
            ],
            'ticketTypeId' => [
                'name' => 'ticketTypeId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the ticket’s type ID.',
            ],
            'price' => [
                'name' => 'price',
                'type' => Type::float(),
                'description' => 'The price of the ticket.',
            ],
            'sku' => [
                'name' => 'sku',
                'type' => Type::string(),
                'description' => 'The SKU of the ticket.',
            ],
        ]);
    }
}
