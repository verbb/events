<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\PurchasedTicket;

use Craft;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;

use yii\web\HttpException;

class DownloadsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionPdf(): \craft\web\Response|string
    {
        $attributes = [];
        $ticket = [];
        $request = Craft::$app->getRequest();

        $tickets = [];
        $order = [];
        $lineItem = null;

        $number = $request->getParam('number');
        $option = $request->getParam('option', '');
        $lineItemId = $request->getParam('lineItemId', '');
        $ticketId = $request->getParam('ticketId', '');

        $format = $request->getParam('format');
        $attach = $request->getParam('attach');

        if ($number) {
            $order = Commerce::getInstance()->getOrders()->getOrderByNumber($number);

            if (!$order) {
                throw new HttpException('No Order Found');
            }
        }

        if ($lineItemId) {
            $lineItem = Commerce::getInstance()->getLineItems()->getLineItemById($lineItemId);

            $attributes['lineItemId'] = $lineItem->id;
        }

        $purchasedTickets = PurchasedTicket::find();

        if ($ticketId) {
            $purchasedTickets->id($ticketId);
        } else {
            $purchasedTickets->orderId($order->id);
        }
        $purchasedTickets->all();

        $pdf = Events::$plugin->getPdf()->renderPdf($purchasedTickets, $order, $lineItem, $option);
        $filenameFormat = Events::$plugin->getSettings()->ticketPdfFilenameFormat;

        $fileName = $this->getView()->renderObjectTemplate($filenameFormat, $order);

        if (!$fileName) {
            if ($order) {
                $fileName = 'Ticket-' . $order->number;
            } else if ($purchasedTickets) {
                $fileName = 'Ticket-' . $ticket[0]->ticketSku;
            }
        }

        $options = [
            'mimeType' => 'application/pdf',
        ];

        if ($attach) {
            $options['inline'] = true;
        }

        if ($format === 'plain') {
            return $pdf;
        }

        return Craft::$app->getResponse()->sendContentAsFile($pdf, $fileName . '.pdf', $options);
    }
}
