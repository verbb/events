<?php
namespace verbb\events\services;

use verbb\events\elements\PurchasedTicket;

use Craft;

use yii\base\Component;

use DateTime;

class PurchasedTickets extends Component
{
    // Public Methods
    // =========================================================================

    public function getPurchasedTicketById(int $id, $siteId = null): ?PurchasedTicket
    {
        return Craft::$app->getElements()->getElementById($id, PurchasedTicket::class, $siteId);
    }

    public function checkInPurchasedTicket(PurchasedTicket $purchasedTicket): void
    {
        $purchasedTicket->checkedIn = true;
        $purchasedTicket->checkedInDate = new DateTime();

        Craft::$app->getElements()->saveElement($purchasedTicket);
    }

    public function checkOutPurchasedTicket(PurchasedTicket $purchasedTicket): void
    {
        $purchasedTicket->checkedIn = false;
        $purchasedTicket->checkedInDate = null;

        Craft::$app->getElements()->saveElement($purchasedTicket);
    }
}
