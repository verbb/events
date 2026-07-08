<?php
namespace verbb\events\elements\conditions\purchasedtickets;

use verbb\events\elements\PurchasedTicket;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;

class ReservationStatusConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    // Public Methods
    // =========================================================================

    public function getLabel(): string
    {
        return Craft::t('events', 'Reservation Status');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['reservationStatus'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var \verbb\events\elements\db\PurchasedTicketQuery $query */
        $query->reservationStatus($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        /** @var PurchasedTicket $element */
        return $this->matchValue($element->reservationStatus);
    }


    // Protected Methods
    // =========================================================================

    protected function options(): array
    {
        return PurchasedTicket::reservationStatuses();
    }
}
