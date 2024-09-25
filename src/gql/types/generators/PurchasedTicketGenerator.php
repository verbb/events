<?php
namespace verbb\events\gql\types\generators;

use verbb\events\Events;
use verbb\events\elements\PurchasedTicket;
use verbb\events\gql\interfaces\PurchasedTicketInterface;
use verbb\events\gql\types\PurchasedTicketType;

use Craft;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\helpers\Gql as GqlHelper;

class PurchasedTicketGenerator implements GeneratorInterface
{
    // Static Methods
    // =========================================================================

    public static function generateTypes(mixed $context = null): array
    {
        $eventTypes = Events::$plugin->getEventTypes()->getAllEventTypes();
        $gqlTypes = [];

        foreach ($eventTypes as $eventType) {
            $typeName = PurchasedTicket::gqlTypeNameByContext($eventType);
            $requiredContexts = PurchasedTicket::gqlScopesByContext($eventType);

            if (!GqlHelper::isSchemaAwareOf($requiredContexts)) {
                continue;
            }

            $contentFields = $eventType->getCustomFields();
            $contentFieldGqlTypes = [];

            foreach ($contentFields as $contentField) {
                $contentFieldGqlTypes[$contentField->handle] = $contentField->getContentGqlType();
            }

            $eventTypeFields = Craft::$app->getGql()->prepareFieldDefinitions(array_merge(PurchasedTicketInterface::getFieldDefinitions(), $contentFieldGqlTypes), $typeName);

            $gqlTypes[$typeName] = GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new PurchasedTicketType([
                'name' => $typeName,
                'fields' => function() use ($eventTypeFields) {
                    return $eventTypeFields;
                },
            ]));
        }

        return $gqlTypes;
    }
}
