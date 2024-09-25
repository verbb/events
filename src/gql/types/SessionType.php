<?php
namespace verbb\events\gql\types;

use verbb\events\gql\interfaces\SessionInterface;

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
}
