<?php
namespace verbb\events\gql\types;

use verbb\events\gql\interfaces\SessionInterface;
use verbb\events\gql\resolvers\SessionResolver;

use craft\gql\types\elements\Element;

use GraphQL\Type\Definition\ResolveInfo;

class SessionType extends Element
{
    // Public Methods
    // =========================================================================

    public function __construct(array $config)
    {
        $config['interfaces'] = [
            SessionInterface::getType(),
        ];

        parent::__construct($config);
    }


    // Protected Methods
    // =========================================================================

    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        if ($resolveInfo->fieldName === 'event') {
            return SessionResolver::resolveEvent($source);
        }

        return parent::resolve($source, $arguments, $context, $resolveInfo);
    }
}
