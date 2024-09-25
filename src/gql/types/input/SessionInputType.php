<?php
namespace verbb\events\gql\types\input;

use verbb\events\gql\arguments\elements\SessionArguments;

use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InputObjectType;

class SessionInputType extends InputObjectType
{
    // Static Methods
    // =========================================================================

    public static function getType(): mixed
    {
        $typeName = 'SessionInput';

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() {
                return SessionArguments::getArguments();
            },
        ]));
    }
}
