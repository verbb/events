<?php
namespace verbb\events\gql\types;

use verbb\events\gql\interfaces\EventInterface;

use craft\gql\types\elements\Element;

use GraphQL\Type\Definition\ResolveInfo;

class EventType extends Element
{
    // Public Methods
    // =========================================================================

    public function __construct(array $config)
    {
        $config['interfaces'] = [
            EventInterface::getType(),
        ];

        parent::__construct($config);
    }


    // Protected Methods
    // =========================================================================

    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        $fieldName = $resolveInfo->fieldName;

        return match ($fieldName) {
            'eventTypeHandle' => $source->getType()->handle,
            'eventTypeId' => $source->getType()->id,
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };
    }
}
