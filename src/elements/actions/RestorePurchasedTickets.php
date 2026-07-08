<?php
namespace verbb\events\elements\actions;

use verbb\events\Events;

use Craft;
use craft\base\ElementAction;
use craft\elements\db\ElementQueryInterface;

class RestorePurchasedTickets extends ElementAction
{
    // Public Methods
    // =========================================================================

    public function getTriggerLabel(): string
    {
        return Craft::t('events', 'Restore tickets');
    }

    public function getConfirmationMessage(): ?string
    {
        return Craft::t('events', 'Are you sure you want to restore the selected purchased tickets? This will reserve their event capacity again.');
    }

    public function performAction(ElementQueryInterface $query = null): bool
    {
        if (!$query) {
            return false;
        }

        $restored = 0;

        foreach ($query->all() as $purchasedTicket) {
            if (Events::$plugin->getPurchasedTickets()->restorePurchasedTicket($purchasedTicket)) {
                $restored++;
            }
        }

        if ($restored === 0) {
            $this->setMessage(Craft::t('events', 'No purchased tickets were restored.'));

            return false;
        }

        $this->setMessage(Craft::t('events', 'Purchased tickets restored.'));

        return true;
    }
}
