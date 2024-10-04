<?php
namespace verbb\events\elements\conditions\events;

use verbb\events\elements\db\EventQuery;
use verbb\events\elements\Event;

use Craft;
use craft\base\conditions\BaseDateRangeConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;

class StartDateConditionRule extends BaseDateRangeConditionRule implements ElementConditionRuleInterface
{
    // Public Methods
    // =========================================================================

    public function getLabel(): string
    {
        return Craft::t('events', 'Start Date');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['startDate'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->startDate($this->queryParamValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->startDate);
    }
}
