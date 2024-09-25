<?php
namespace verbb\events\gql\interfaces;

use verbb\events\elements\Ticket;
use verbb\events\gql\types\generators\TicketGenerator;

use Craft;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use craft\gql\TypeManager;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class TicketInterface extends Element
{
    // Static Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return TicketGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all tickets.',
            'resolveType' => function(Ticket $value) {
                return $value->getGqlTypeName();
            },
        ]));

        TicketGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'TicketInterface';
    }

    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'event' => [
                'name' => 'event',
                'type' => EventInterface::getType(),
                'description' => 'The event associated with the ticket.',
            ],
            'session' => [
                'name' => 'session',
                'type' => SessionInterface::getType(),
                'description' => 'The session associated with the ticket.',
            ],
            'type' => [
                'name' => 'type',
                'type' => TicketTypeInterface::getType(),
                'description' => 'The ticket‘s type.',
            ],
            'price' => [
                'name' => 'price',
                'type' => Type::float(),
                'description' => 'The ticket‘s price.',
            ],
            'quantity' => [
                'name' => 'quantity',
                'type' => Type::int(),
                'description' => 'The ticket‘s quantity.',
            ],
            'sku' => [
                'name' => 'sku',
                'type' => Type::string(),
                'description' => 'The ticket‘s SKU.',
            ],
        ]), self::getName());
    }
}
