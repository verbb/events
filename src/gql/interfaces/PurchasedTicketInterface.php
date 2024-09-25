<?php
namespace verbb\events\gql\interfaces;

use verbb\events\elements\PurchasedTicket;
use verbb\events\gql\types\generators\PurchasedTicketGenerator;

use Craft;
use craft\gql\GqlEntityRegistry;
use craft\gql\interfaces\Element;
use craft\gql\types\DateTime;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class PurchasedTicketInterface extends Element
{
    // Static Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return PurchasedTicketGenerator::class;
    }

    public static function getType($fields = null): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all purchased tickets.',
            'resolveType' => function(PurchasedTicket $value) {
                return $value->getGqlTypeName();
            },
        ]));

        PurchasedTicketGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'PurchasedTicketInterface';
    }

    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'event' => [
                'name' => 'event',
                'type' => EventInterface::getType(),
                'description' => 'The event associated with the purchased ticket.',
            ],
            'session' => [
                'name' => 'session',
                'type' => SessionInterface::getType(),
                'description' => 'The session associated with the purchased ticket.',
            ],
            'ticketType' => [
                'name' => 'ticketType',
                'type' => TicketTypeInterface::getType(),
                'description' => 'The ticket type associated with the purchased ticket.',
            ],
            'ticket' => [
                'name' => 'ticket',
                'type' => TicketInterface::getType(),
                'description' => 'The ticket associated with the purchased ticket.',
            ],
            'user' => [
                'name' => 'user',
                'type' => Type::string(),
                'description' => 'The user who purchased the ticket.',
            ],
            'firstName' => [
                'name' => 'firstName',
                'type' => Type::string(),
                'description' => 'The first name of the ticket holder.',
            ],
            'lastName' => [
                'name' => 'lastName',
                'type' => Type::string(),
                'description' => 'The last name of the ticket holder.',
            ],
            'email' => [
                'name' => 'email',
                'type' => Type::string(),
                'description' => 'The email of the ticket holder.',
            ],
            'checkedIn' => [
                'name' => 'checkedIn',
                'type' => Type::boolean(),
                'description' => 'Whether the ticket holder has checked in.',
            ],
            'checkedInDate' => [
                'name' => 'checkedInDate',
                'type' => DateTime::getType(),
                'description' => 'The date and time the ticket holder checked in.',
            ],
        ]), self::getName());
    }
}
