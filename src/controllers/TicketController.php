<?php
namespace verbb\events\controllers;

use verbb\events\Events;

use Craft;
use craft\web\Controller;

class TicketController extends Controller
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionCheckin(array $variables = array())
    {
        $sku = Craft::$app->request->getParam('sku');

        if (!$sku) {
            return $this->asErrorJson(Craft::t('events', 'Missing required ticket SKU.'));
        }

        $purchasedTicket = Events::$plugin->getPurchasedTickets()->getPurchasedTicket([
            'ticketSku' => $sku,
        ]);

        if (!$purchasedTicket->id) {
            return $this->asErrorJson(Craft::t('events', 'Could not find ticket SKU.'));
        }

        if ($purchasedTicket->checkedIn) {
            return $this->asErrorJson(Craft::t('events', 'Ticket already checked in.'));
        }

        Events::$plugin->getPurchasedTickets()->checkInPurchasedTicket($purchasedTicket);

        return $this->asJson([
            'success' => true,
            'checkedInDate' => $purchasedTicket->checkedInDate,
        ]);
    }

}