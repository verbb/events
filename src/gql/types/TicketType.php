<?php
namespace verbb\events\gql\types;

use verbb\events\gql\interfaces\TicketInterface;

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
}
