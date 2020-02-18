<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\PurchasedTicket;

use Craft;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;

use yii\web\HttpException;
use yii\web\Response;

class DownloadsController extends Controller
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionPdf()
    {
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

        $purchasedTickets = PurchasedTicket::find()
            ->orderId($order->id)
            ->all();

        $pdf = Events::getInstance()->getPdf()->renderPdf($purchasedTickets, $order, $lineItem, $option);
        $filenameFormat = Events::getInstance()->getSettings()->ticketPdfFilenameFormat;

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
        } else {
            return Craft::$app->getResponse()->sendContentAsFile($pdf, $fileName . '.pdf', $options);
        }
    }
}