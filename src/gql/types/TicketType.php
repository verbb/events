<?php
namespace verbb\events\gql\types;

use verbb\events\gql\interfaces\TicketInterface;
use verbb\events\helpers\GqlRelations;

use craft\gql\types\elements\Element;

use GraphQL\Type\Definition\ResolveInfo;

class TicketType extends Element
{
    // Public Methods
    // =========================================================================

    public function __construct(array $config)
    {
        $config['interfaces'] = [
            TicketInterface::getType(),
        ];

        parent::__construct($config);
    }


    // Protected Methods
    // =========================================================================

    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        if (in_array($resolveInfo->fieldName, ['event', 'session', 'type'], true)) {
            return GqlRelations::visible($source->{$resolveInfo->fieldName});
        }

        return parent::resolve($source, $arguments, $context, $resolveInfo);
    }
}
