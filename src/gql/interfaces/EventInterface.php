<?php
namespace verbb\events\gql\interfaces;

use verbb\events\elements\Event;
use verbb\events\gql\types\generators\EventGenerator;

use Craft;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use craft\gql\TypeManager;
use craft\gql\types\DateTime;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class EventInterface extends Element
{
    // Static Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return EventGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all events.',
            'resolveType' => function(Event $value) {
                return $value->getGqlTypeName();
            },
        ]));

        EventGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'EventInterface';
    }

    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'sessions' => [
                'name' => 'sessions',
                'type' => Type::listOf(SessionInterface::getType()),
                'description' => 'The sessions associated with this event.',
            ],
            'ticketTypes' => [
                'name' => 'ticketTypes',
                'type' => Type::listOf(TicketTypeInterface::getType()),
                'description' => 'The ticket types associated with this event.',
            ],
            'tickets' => [
                'name' => 'tickets',
                'type' => Type::listOf(TicketInterface::getType()),
                'description' => 'The tickets associated with this event.',
            ],
            'purchasedTickets' => [
                'name' => 'purchasedTickets',
                'type' => Type::listOf(PurchasedTicketInterface::getType()),
                'description' => 'The purchased tickets associated with this event.',
            ],
            'eventTypeId' => [
                'name' => 'eventTypeId',
                'type' => Type::int(),
                'description' => 'The ID of the event type that contains the event.',
            ],
            'eventTypeHandle' => [
                'name' => 'eventTypeHandle',
                'type' => Type::string(),
                'description' => 'The handle of the event type that contains the event.',
            ],
            'startDate' => [
                'name' => 'startDate',
                'type' => DateTime::getType(),
                'description' => 'The first session‘s start date.',
            ],
            'endDate' => [
                'name' => 'endDate',
                'type' => DateTime::getType(),
                'description' => 'The last session‘s start date.',
            ],
            'allDay' => [
                'name' => 'allDay',
                'type' => Type::boolean(),
                'description' => 'Whether the event is an all-day event.',
            ],
            'capacity' => [
                'name' => 'capacity',
                'type' => Type::int(),
                'description' => 'The event‘s capacity.',
            ],
            'availableCapacity' => [
                'name' => 'availableCapacity',
                'type' => Type::int(),
                'description' => 'The event‘s available capacity.',
            ],
        ]), self::getName());
    }
}
