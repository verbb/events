<?php
namespace verbb\events\elements\conditions\events;

use verbb\events\elements\db\EventQuery;
use verbb\events\elements\Event;

use Craft;
use craft\base\conditions\BaseDateRangeConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;

class EndDateConditionRule extends BaseDateRangeConditionRule implements ElementConditionRuleInterface
{
    // Public Methods
    // =========================================================================

    public function getLabel(): string
    {
        return Craft::t('events', 'End Date');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['endDate'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->endDate($this->queryParamValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->endDate);
    }
}
