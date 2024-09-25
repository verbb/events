<?php
namespace verbb\events\gql\arguments;

use verbb\events\Events;

use craft\gql\base\ElementArguments;
use craft\gql\types\DateTime;
use craft\gql\types\QueryArgument;

use GraphQL\Type\Definition\Type;

class TicketTypeArguments extends ElementArguments
{
    // Static Methods
    // =========================================================================

    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'eventId' => [
                'name' => 'eventId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the ticket type’s event ID.',
            ],
            'price' => [
                'name' => 'price',
                'type' => Type::float(),
                'description' => 'The price of the ticket type.',
            ],
            'capacity' => [
                'name' => 'capacity',
                'type' => Type::int(),
                'description' => 'The capacity of the ticket type.',
            ],
        ]);
    }

    public static function getContentArguments(): array
    {
        return array_merge(parent::getContentArguments(), Events::$plugin->getTicketTypes()->getTicketTypeGqlContentArguments());
    }
}
