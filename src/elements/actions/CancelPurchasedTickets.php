<?php
namespace verbb\events\elements\actions;

use verbb\events\Events;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;

class CancelPurchasedTickets extends ElementAction
{
    // Public Methods
    // =========================================================================

    public function getTriggerLabel(): string
    {
        return Craft::t('events', 'Cancel tickets');
    }

    public function getConfirmationMessage(): ?string
    {
        return Craft::t('events', 'Are you sure you want to cancel the selected purchased tickets? This will release their event capacity.');
    }

    public function performAction(ElementQueryInterface $query = null): bool
    {
        if (!$query) {
            return false;
        }

        $cancelled = 0;

        foreach ($query->all() as $purchasedTicket) {
            if (Events::$plugin->getPurchasedTickets()->cancelPurchasedTicket($purchasedTicket)) {
                $cancelled++;
            }
        }

        if ($cancelled === 0) {
            $this->setMessage(Craft::t('events', 'No purchased tickets were cancelled.'));

            return false;
        }

        $this->setMessage(Craft::t('events', 'Purchased tickets cancelled.'));

        return true;
    }
}
