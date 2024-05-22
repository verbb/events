<?php
namespace verbb\events\services;

use verbb\events\elements\PurchasedTicket;
use verbb\events\events\PurchasedTicketEvent;
use verbb\events\records\PurchasedTicket as PurchasedTicketRecord;

use Craft;
use craft\base\ElementInterface;

use yii\base\Component;

use DateTime;

class PurchasedTickets extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_CHECK_IN = 'beforeCheckIn';
    public const EVENT_AFTER_CHECK_IN = 'afterCheckIn';
    public const EVENT_BEFORE_CHECK_OUT = 'beforeCheckOut';
    public const EVENT_AFTER_CHECK_OUT = 'afterCheckOut';


    // Public Methods
    // =========================================================================

    public function getPurchasedTicketById(int $id, $siteId = null): ?PurchasedTicket
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($id, PurchasedTicket::class, $siteId);
    }

    public function checkInPurchasedTicket(PurchasedTicket $purchasedTicket): bool
    {
        $purchasedTicket->checkedIn = true;
        $purchasedTicket->checkedInDate = new DateTime();

        // Trigger a 'beforeCheckIn' event
        $event = new PurchasedTicketEvent([
            'purchasedTicket' => $purchasedTicket,
        ]);
        $this->trigger(self::EVENT_BEFORE_CHECK_IN, $event);

        if (!$event->isValid) {
            return false;
        }

        if (!Craft::$app->getElements()->saveElement($event->purchasedTicket)) {
            return false;
        }

        // Trigger a 'afterCheckIn' event
        $this->trigger(self::EVENT_AFTER_CHECK_IN, new PurchasedTicketEvent([
            'purchasedTicket' => $event->purchasedTicket,
        ]));

        return true;
    }

    public function unCheckInPurchasedTicket(PurchasedTicket $purchasedTicket): bool
    {
        $purchasedTicket->checkedIn = false;
        $purchasedTicket->checkedInDate = null;

        // Trigger a 'beforeCheckOut' event
        $event = new PurchasedTicketEvent([
            'purchasedTicket' => $purchasedTicket,
        ]);
        $this->trigger(self::EVENT_BEFORE_CHECK_OUT, $event);

        if (!$event->isValid) {
            return false;
        }

        if (!Craft::$app->getElements()->saveElement($event->purchasedTicket)) {
            return false;
        }

        // Trigger a 'afterCheckOut' event
        $this->trigger(self::EVENT_AFTER_CHECK_OUT, new PurchasedTicketEvent([
            'purchasedTicket' => $event->purchasedTicket,
        ]));

        return true;
    }
}
