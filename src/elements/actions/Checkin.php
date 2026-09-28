<?php
namespace verbb\events\elements\actions;

use verbb\events\Events;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;

class Checkin extends ElementAction
{
    // Public Methods
    // =========================================================================

    public function getTriggerLabel(): string
    {
        return Craft::t('events', 'Check in');
    }

    public function performAction(ElementQueryInterface $query = null): bool
    {
        if (!$query) {
            return false;
        }

        $success = true;

        foreach ($query->all() as $purchasedTicket) {
            if (!$purchasedTicket->getIsActive()) {
                continue;
            }

            if (!Events::$plugin->getPurchasedTickets()->checkInPurchasedTicket($purchasedTicket)) {
                $success = false;
            }
        }

        $this->setMessage($success
            ? Craft::t('events', 'Tickets checked in')
            : Craft::t('events', 'Couldn’t check in all selected tickets.'));

        return $success;
    }
}
