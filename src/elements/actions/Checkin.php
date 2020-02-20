<?php
namespace verbb\events\elements\actions;

use verbb\events\Events;

use Craft;
use craft\base\Element;
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
	
    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query = null): bool
    {
        if (!$query) {
            return false;
        }

        foreach ($query->all() as $purchasedTicket) {
            Events::$plugin->getPurchasedTickets()->checkInPurchasedTicket($purchasedTicket);
        }

        $this->setMessage(Craft::t('events', 'Tickets checked in'));

        return true;
    }
}
