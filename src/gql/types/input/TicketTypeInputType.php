<?php
namespace verbb\events\gql\types\input;

use verbb\events\gql\arguments\elements\TicketTypeArguments;

use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InputObjectType;

class TicketTypeInputType extends InputObjectType
{
    // Static Methods
    // =========================================================================

    public static function getType(): mixed
    {
        $typeName = 'TicketTypeInput';

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() {
                return TicketTypeArguments::getArguments();
            },
        ]));
    }
}
