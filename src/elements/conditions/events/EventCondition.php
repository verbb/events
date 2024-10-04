<?php
namespace verbb\events\elements\conditions\events;

use craft\elements\conditions\ElementCondition;

class EventCondition extends ElementCondition
{
    // Protected Methods
    // =========================================================================

    protected function selectableConditionRules(): array
    {
        return array_merge(parent::selectableConditionRules(), [
            EventTypeConditionRule::class,
            StartDateConditionRule::class,
            EndDateConditionRule::class,
        ]);
    }
}
