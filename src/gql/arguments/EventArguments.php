<?php
namespace verbb\events\gql\arguments;

use verbb\events\Events;
use verbb\events\elements\Event;

use Craft;
use craft\gql\base\ElementArguments;
use craft\gql\types\DateTime;
use craft\gql\types\QueryArgument;

use GraphQL\Type\Definition\Type;

class EventArguments extends ElementArguments
{
    // Static Methods
    // =========================================================================

    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'startDate' => [
                'name' => 'startDate',
                'type' => DateTime::getType(),
                'description' => 'The start date of the event.',
            ],
            'endDate' => [
                'name' => 'endDate',
                'type' => DateTime::getType(),
                'description' => 'The end date of the event.',
            ],
            'allDay' => [
                'name' => 'allDay',
                'type' => Type::boolean(),
                'description' => 'Whether the event is an all-day event.',
            ],
            'capacity' => [
                'name' => 'capacity',
                'type' => Type::int(),
                'description' => 'The capacity of the event.',
            ],
            'availableCapacity' => [
                'name' => 'availableCapacity',
                'type' => Type::int(),
                'description' => 'The available capacity of the event.',
            ],
            'type' => [
                'name' => 'type',
                'type' => Type::listOf(Type::string()),
                'description' => 'Narrows the query results based on the event type the events belong to per the event type’s handles.',
            ],
            'typeId' => [
                'name' => 'typeId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the event types the events belong to, per the event type IDs.',
            ],
        ]);
    }

    public static function getContentArguments(): array
    {
        $eventTypes = Events::$plugin->getEventTypes()->getAllEventTypes();
        $eventTypeFieldArguments = Craft::$app->getGql()->getContentArguments($eventTypes, Event::class);

        return array_merge(parent::getContentArguments(), $eventTypeFieldArguments);
    }
}
