<?php
namespace verbb\events\gql\types;

use verbb\events\gql\interfaces\TicketTypeInterface;

use craft\gql\types\elements\Element;

use GraphQL\Type\Definition\ResolveInfo;

class TicketTypeType extends Element
{
    // Public Methods
    // =========================================================================

    public function __construct(array $config)
    {
        $config['interfaces'] = [
            TicketTypeInterface::getType(),
        ];

        parent::__construct($config);
    }
}
