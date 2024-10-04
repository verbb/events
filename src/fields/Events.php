<?php
namespace verbb\events\fields;

use verbb\events\elements\Event;
use verbb\events\elements\db\EventQuery;
use verbb\events\gql\arguments\EventArguments;
use verbb\events\gql\interfaces\EventInterface;
use verbb\events\gql\resolvers\EventResolver;
use verbb\events\helpers\Gql;

use Craft;
use craft\elements\conditions\ElementCondition;
use craft\fields\BaseRelationField;
use craft\models\GqlSchema;

use GraphQL\Type\Definition\Type;

class Events extends BaseRelationField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('events', 'Events');
    }

    public static function icon(): string
    {
        return '@verbb/events/icon-mask.svg';
    }

    public static function elementType(): string
    {
        return Event::class;
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('events', 'Add an event');
    }

    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', EventQuery::class, ElementCollection::class, Event::class);
    }


    // Public Methods
    // =========================================================================

    public function includeInGqlSchema(GqlSchema $schema): bool
    {
        return Gql::canQueryEvents($schema);
    }

    public function getContentGqlType(): Type|array
    {
        return [
            'name' => $this->handle,
            'type' => Type::nonNull(Type::listOf(EventInterface::getType())),
            'args' => EventArguments::getArguments(),
            'resolve' => EventResolver::class . '::resolve',
        ];
    }


    // Protected Methods
    // =========================================================================

    protected function createSelectionCondition(): ?ElementCondition
    {
        return Event::createCondition();
    }

}
