<?php
namespace verbb\events\elements\conditions\purchasedtickets;

use verbb\events\elements\Event;

use Craft;
use craft\base\conditions\BaseElementSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;

class EventConditionRule extends BaseElementSelectConditionRule implements ElementConditionRuleInterface
{
    // Public Methods
    // =========================================================================

    public function getLabel(): string
    {
        return Craft::t('events', 'Event');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['event', 'eventId'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->event($this->getElementId());
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->eventId);
    }


    // Protected Methods
    // =========================================================================

    protected function elementType(): string
    {
        return Event::class;
    }
}
