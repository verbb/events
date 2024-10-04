<?php
namespace verbb\events\elements\conditions\purchasedtickets;

use craft\elements\conditions\ElementCondition;

class PurchasedTicketCondition extends ElementCondition
{
    // Protected Methods
    // =========================================================================

    protected function selectableConditionRules(): array
    {
        return array_merge(parent::selectableConditionRules(), [
            EventConditionRule::class,
            SessionConditionRule::class,
            TicketTypeConditionRule::class,
        ]);
    }
}
