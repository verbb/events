<?php 
namespace verbb\events\gql\interfaces;

use verbb\events\elements\TicketType;
use verbb\events\gql\types\generators\TicketTypeGenerator;

use Craft;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use craft\gql\TypeManager;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class TicketTypeInterface extends Element
{
    // Static Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return TicketTypeGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all ticket types.',
            'resolveType' => function(TicketType $value) {
                return $value->getGqlTypeName();
            },
        ]));

        TicketTypeGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'TicketTypeInterface';
    }

    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'event' => [
                'name' => 'event',
                'type' => EventInterface::getType(),
                'description' => 'The event associated with the ticket type.',
            ],
            'price' => [
                'name' => 'price',
                'type' => Type::float(),
                'description' => 'The ticket type‘s price.',
            ],
            'capacity' => [
                'name' => 'capacity',
                'type' => Type::int(),
                'description' => 'The ticket type‘s capacity.',
            ],
        ]), self::getName());
    }
}
