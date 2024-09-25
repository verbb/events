<?php
namespace verbb\events\gql\arguments;

use verbb\events\Events;

use craft\gql\base\ElementArguments;
use craft\gql\types\DateTime;
use craft\gql\types\QueryArgument;

use GraphQL\Type\Definition\Type;

class SessionArguments extends ElementArguments
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
            'startDate' => [
                'name' => 'startDate',
                'type' => DateTime::getType(),
                'description' => 'The start date of the session.',
            ],
            'endDate' => [
                'name' => 'endDate',
                'type' => DateTime::getType(),
                'description' => 'The end date of the session.',
            ],
            'allDay' => [
                'name' => 'allDay',
                'type' => Type::boolean(),
                'description' => 'Whether the session is an all-day session.',
            ],
            'capacity' => [
                'name' => 'capacity',
                'type' => Type::int(),
                'description' => 'The capacity of the session.',
            ],
            'availableCapacity' => [
                'name' => 'availableCapacity',
                'type' => Type::int(),
                'description' => 'The available capacity of the session.',
            ],
        ]);
    }

    public static function getContentArguments(): array
    {
        return array_merge(parent::getContentArguments(), Events::$plugin->getSessions()->getSessionGqlContentArguments());
    }
}
