<?php
namespace verbb\events\gql\types\input;

use verbb\events\gql\arguments\elements\EventArguments;

use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InputObjectType;

class EventInputType extends InputObjectType
{
    // Static Methods
    // =========================================================================

    public static function getType(): mixed
    {
        $typeName = 'EventInput';

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new InputObjectType([
            'name' => $typeName,
            'fields' => function() {
                return EventArguments::getArguments();
            },
        ]));
    }
}
