<?php
namespace Craft;

class Events_TicketController extends BaseController
{
    protected $allowAnonymous = true;

    /**
     * Frontend controller for actioning and validate ticket check ins
     *
     * Route: actions/events/ticket/checkin?sku=<sku>
     *
     * @param array $variables
     *
     * return json
     */
    public function actionCheckin(array $variables = array())
    {
        $sku = craft()->request->getParam('sku');

        if (!$sku) {
            $this->returnErrorJson('Missing required ticket SKU.');
        }

        $purchasedTicket = EventsHelper::getPurchasedTicketsService()->getByAttributes(array(
            'ticketSku' => $sku,
        ));

        if (!$purchasedTicket->id) {
            $this->returnErrorJson('Could not find ticket SKU.');
        }

        if ($purchasedTicket->checkedIn) {
            $this->returnErrorJson('Ticket already checked in.');
        }

        $purchasedTicket->checkedIn = true;
        $purchasedTicket->checkedInDate = new DateTime();

        $record = Events_PurchasedTicketRecord::model()->findById($purchasedTicket->id);
        $record->checkedIn = $purchasedTicket->checkedIn;
        $record->checkedInDate = $purchasedTicket->checkedInDate;

        $record->save(false);

        $this->returnJson(array(
            'success' => 'Ticket checked in.',
            'checkedInDate' => $purchasedTicket->checkedInDate,
        ));
    }

}