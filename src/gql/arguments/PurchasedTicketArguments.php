<?php
namespace verbb\events\gql\arguments;

use craft\gql\base\ElementArguments;
use craft\gql\types\DateTime;
use craft\gql\types\QueryArgument;

use GraphQL\Type\Definition\Type;

class PurchasedTicketArguments extends ElementArguments
{
    // Static Methods
    // =========================================================================

    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'ticketId' => [
                'name' => 'ticketId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'The ID of the associated ticket.',
            ],
            'eventId' => [
                'name' => 'eventId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'The ID of the associated event.',
            ],
            'sessionId' => [
                'name' => 'sessionId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'The ID of the associated session.',
            ],
            'ticketTypeId' => [
                'name' => 'ticketTypeId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'The ID of the associated ticket type.',
            ],
            'userId' => [
                'name' => 'userId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'The ID of the user who purchased the ticket.',
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
        ]);
    }
}
