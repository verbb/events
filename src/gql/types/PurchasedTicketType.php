<?php
namespace verbb\events\gql\types;

use verbb\events\gql\interfaces\PurchasedTicketInterface;
use verbb\events\helpers\Gql as GqlHelper;
use verbb\events\helpers\GqlRelations;

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


    // Protected Methods
    // =========================================================================

    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        if (in_array($resolveInfo->fieldName, ['event', 'session', 'ticketType', 'ticket'], true)) {
            return GqlRelations::visible($source->{$resolveInfo->fieldName});
        }

        // Configurable titles can include or transform the check-in identifier.
        if (in_array($resolveInfo->fieldName, ['uid', 'title'], true)) {
            $eventTypeUid = $source->getEvent()?->getType()->uid;

            if (!$eventTypeUid ||
                !GqlHelper::canSchema('eventsEventTypes.' . $eventTypeUid) ||
                !GqlHelper::canSchema('eventsPurchasedTickets.' . $eventTypeUid) ||
                !GqlHelper::canSchema('eventsPurchasedTickets.' . $eventTypeUid, 'checkIn')
            ) {
                return null;
            }
        }

        return parent::resolve($source, $arguments, $context, $resolveInfo);
    }
}
