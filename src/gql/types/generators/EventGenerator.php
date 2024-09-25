<?php
namespace verbb\events\gql\types\generators;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\gql\interfaces\EventInterface;
use verbb\events\gql\types\EventType;

use Craft;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql as GqlHelper;

class EventGenerator implements GeneratorInterface
{
    // Static Methods
    // =========================================================================

    public static function generateTypes(mixed $context = null): array
    {
        $eventTypes = Events::$plugin->getEventTypes()->getAllEventTypes();
        $gqlTypes = [];

        foreach ($eventTypes as $eventType) {
            $typeName = Event::gqlTypeNameByContext($eventType);
            $requiredContexts = Event::gqlScopesByContext($eventType);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts)) {
                continue;
            }

            $contentFields = $eventType->getCustomFields();
            $contentFieldGqlTypes = [];

            foreach ($contentFields as $contentField) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }

            $eventTypeFields = Craft::$app->getGql()->prepareFieldDefinitions(array_merge(EventInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new EventType([
                'name' => $typeName,
                'fields' => function() use ($eventTypeFields) {
                    return $eventTypeFields;
                },
            ]));
        }

        return $gqlTypes;
    }
}
