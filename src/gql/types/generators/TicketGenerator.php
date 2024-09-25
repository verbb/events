<?php
namespace verbb\events\gql\types\generators;

use verbb\events\Events;
use verbb\events\elements\Ticket;
use verbb\events\gql\interfaces\TicketInterface;
use verbb\events\gql\types\TicketType;

use Craft;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql as GqlHelper;

class TicketGenerator implements GeneratorInterface
{
    // Static Methods
    // =========================================================================

    public static function generateTypes(mixed $context = null): array
    {
        $eventTypes = Events::$plugin->getEventTypes()->getAllEventTypes();
        $gqlTypes = [];

        foreach ($eventTypes as $eventType) {
            $typeName = Ticket::gqlTypeNameByContext($eventType);
            $requiredContexts = Ticket::gqlScopesByContext($eventType);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts)) {
                continue;
            }

            $contentFields = $eventType->getCustomFields();
            $contentFieldGqlTypes = [];

            foreach ($contentFields as $contentField) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }

            $eventTypeFields = Craft::$app->getGql()->prepareFieldDefinitions(array_merge(TicketInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new TicketType([
                'name' => $typeName,
                'fields' => function() use ($eventTypeFields) {
                    return $eventTypeFields;
                },
            ]));
        }

        return $gqlTypes;
    }
}
