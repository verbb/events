<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\PurchasedTicket;
use verbb\events\models\Settings;

use Craft;
use craft\web\Controller;
use craft\web\Response;

use craft\commerce\Plugin as Commerce;

use yii\web\HttpException;

class DownloadsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionPdf(): Response|string
    {
        $attributes = [];
        $ticket = [];

        /* @var Settings $settings */
        $settings = Events::$plugin->getSettings();

        $tickets = [];
        $order = [];
        $lineItem = null;

        $number = $this->request->getParam('number');
        $option = $this->request->getParam('option', '');
        $lineItemId = $this->request->getParam('lineItemId', '');
        $ticketId = $this->request->getParam('ticketId', '');

        $format = $this->request->getParam('format');
        $attach = $this->request->getParam('attach');

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
        $filenameFormat = $settings->ticketPdfFilenameFormat;

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
