<?php
namespace verbb\events\gql\types\generators;

use verbb\events\Events;
use verbb\events\elements\TicketType;
use verbb\events\gql\interfaces\TicketTypeInterface;
use verbb\events\gql\types\TicketTypeType;

use Craft;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql as GqlHelper;

class TicketTypeGenerator implements GeneratorInterface
{
    // Static Methods
    // =========================================================================

    public static function generateTypes(mixed $context = null): array
    {
        $eventTypes = Events::$plugin->getEventTypes()->getAllEventTypes();
        $gqlTypes = [];

        foreach ($eventTypes as $eventType) {
            $typeName = TicketType::gqlTypeNameByContext($eventType);
            $requiredContexts = TicketType::gqlScopesByContext($eventType);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts)) {
                continue;
            }

            $contentFields = $eventType->getTicketTypeFieldLayout()->getCustomFields();
            $contentFieldGqlTypes = [];

            foreach ($contentFields as $contentField) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }

            $eventTypeFields = Craft::$app->getGql()->prepareFieldDefinitions(array_merge(TicketTypeInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new TicketTypeType([
                'name' => $typeName,
                'fields' => function() use ($eventTypeFields) {
                    return $eventTypeFields;
                },
            ]));
        }

        return $gqlTypes;
    }
}
