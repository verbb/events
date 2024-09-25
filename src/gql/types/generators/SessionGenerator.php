<?php
namespace verbb\events\gql\types\generators;

use verbb\events\Events;
use verbb\events\elements\Session;
use verbb\events\gql\interfaces\SessionInterface;
use verbb\events\gql\types\SessionType;

use Craft;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql as GqlHelper;

class SessionGenerator implements GeneratorInterface
{
    // Static Methods
    // =========================================================================

    public static function generateTypes(mixed $context = null): array
    {
        $eventTypes = Events::$plugin->getEventTypes()->getAllEventTypes();
        $gqlTypes = [];

        foreach ($eventTypes as $eventType) {
            $typeName = Session::gqlTypeNameByContext($eventType);
            $requiredContexts = Session::gqlScopesByContext($eventType);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts)) {
                continue;
            }

            $contentFields = $eventType->getSessionFieldLayout()->getCustomFields();
            $contentFieldGqlTypes = [];

            foreach ($contentFields as $contentField) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }

            $eventTypeFields = Craft::$app->getGql()->prepareFieldDefinitions(array_merge(SessionInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new SessionType([
                'name' => $typeName,
                'fields' => function() use ($eventTypeFields) {
                    return $eventTypeFields;
                },
            ]));
        }

        return $gqlTypes;
    }
}
