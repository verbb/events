<?php
namespace verbb\events\services;

use verbb\events\elements\PurchasedTicket;
use verbb\events\records\PurchasedTicketRecord;

use Craft;
use craft\base\ElementInterface;

use yii\base\Component;

use DateTime;

class PurchasedTickets extends Component
{
    // Public Methods
    // =========================================================================

    public function getPurchasedTicketById(int $id, $siteId = null): ?PurchasedTicket
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($id, PurchasedTicket::class, $siteId);
    }

    public function checkInPurchasedTicket(PurchasedTicket $purchasedTicket): void
    {
        $purchasedTicket->checkedIn = true;
        $purchasedTicket->checkedInDate = new DateTime();

        $record = PurchasedTicketRecord::findOne($purchasedTicket->id);
        $record->checkedIn = $purchasedTicket->checkedIn;
        $record->checkedInDate = $purchasedTicket->checkedInDate;

        $record->save(false);
    }

    public function unCheckInPurchasedTicket(PurchasedTicket $purchasedTicket): void
    {
        $purchasedTicket->checkedIn = false;
        $purchasedTicket->checkedInDate = null;

        $record = PurchasedTicketRecord::findOne($purchasedTicket->id);
        $record->checkedIn = $purchasedTicket->checkedIn;
        $record->checkedInDate = $purchasedTicket->checkedInDate;

        $record->save(false);
    }
}