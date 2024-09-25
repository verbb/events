<?php
namespace verbb\events\gql\types;

use verbb\events\gql\interfaces\PurchasedTicketInterface;

use craft\gql\types\elements\Element;

use GraphQL\Type\Definition\ResolveInfo;

class PurchasedTicketType extends Element
{
    // Public Methods
    // =========================================================================

    public function __construct(array $config)
    {
        $config['interfaces'] = [
            PurchasedTicketInterface::getType(),
        ];

        parent::__construct($config);
    }
}
