<?php
namespace verbb\events\services;

use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\Ticket;

use Craft;
use craft\db\Query;
use craft\events\ElementIndexAvailableTableAttributesEvent;
use craft\events\SiteEvent;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\queue\jobs\ResaveElements;

use DateTime;

use yii\base\Component;
use yii\base\Exception;

class PurchasedTickets extends Component
{
    // Public Methods
    // =========================================================================

    public function getPurchasedTicketById(int $id, $siteId = null)
    {
        return Craft::$app->getElements()->getElementById($id, PurchasedTicket::class, $siteId);
    }

    public function checkInPurchasedTicket(PurchasedTicket $purchasedTicket)
    {
        $purchasedTicket->checkedIn = true;
        $purchasedTicket->checkedInDate = new DateTime();

        Craft::$app->getElements()->saveElement($purchasedTicket);
    }

    public function unCheckInPurchasedTicket(PurchasedTicket $purchasedTicket)
    {
        $purchasedTicket->checkedIn = false;
        $purchasedTicket->checkedInDate = null;

        Craft::$app->getElements()->saveElement($purchasedTicket);
    }
}