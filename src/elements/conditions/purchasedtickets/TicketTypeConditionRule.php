<?php
namespace verbb\events\elements\conditions\purchasedtickets;

use verbb\events\elements\TicketType;

use Craft;
use craft\base\conditions\BaseElementSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;

class TicketTypeConditionRule extends BaseElementSelectConditionRule implements ElementConditionRuleInterface
{
    // Public Methods
    // =========================================================================

    public function getLabel(): string
    {
        return Craft::t('events', 'Ticket Type');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['ticketType', 'ticketTypeId'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $query->ticketType($this->getElementId());
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->ticketTypeId);
    }


    // Protected Methods
    // =========================================================================

    protected function elementType(): string
    {
        return TicketType::class;
    }
}
