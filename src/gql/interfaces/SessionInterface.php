<?php
namespace verbb\events\gql\interfaces;

use verbb\events\elements\Session;
use verbb\events\gql\types\generators\SessionGenerator;

use Craft;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use craft\gql\TypeManager;
use craft\gql\types\DateTime;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class SessionInterface extends Element
{
    // Static Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return SessionGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all sessions.',
            'resolveType' => function(Session $value) {
                return $value->getGqlTypeName();
            },
        ]));

        SessionGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'SessionInterface';
    }

    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'event' => [
                'name' => 'event',
                'type' => EventInterface::getType(),
                'description' => 'The event associated with the session.',
            ],
            'startDate' => [
                'name' => 'startDate',
                'type' => DateTime::getType(),
                'description' => 'The session‘s start date.',
            ],
            'endDate' => [
                'name' => 'endDate',
                'type' => DateTime::getType(),
                'description' => 'The session‘s end date.',
            ],
            'allDay' => [
                'name' => 'allDay',
                'type' => Type::boolean(),
                'description' => 'Whether the session is an all-day session.',
            ],
            'capacity' => [
                'name' => 'capacity',
                'type' => Type::int(),
                'description' => 'The session‘s capacity.',
            ],
            'availableCapacity' => [
                'name' => 'availableCapacity',
                'type' => Type::int(),
                'description' => 'The session‘s available capacity.',
            ],
        ]), self::getName());
    }
}
