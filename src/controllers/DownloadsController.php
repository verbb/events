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

use verbb\base\helpers\Locale as LocaleHelper;

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

        $number = $this->request->getRequiredParam('number');
        $option = $this->request->getParam('option', '');
        $lineItemId = $this->request->getParam('lineItemId', '');
        $ticketId = $this->request->getParam('ticketId', '');

        $format = $this->request->getParam('format');
        $attach = $this->request->getParam('attach');

        $siteHandle = $this->request->getParam('site');
        $site = Craft::$app->getSites()->getPrimarySite();

        if ($siteHandle) {
            if ($requestedSite = Craft::$app->getSites()->getSiteByHandle($siteHandle)) {
                $site = $requestedSite;
            }
        }

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

        $query = PurchasedTicket::find();

        if ($ticketId) {
            $query->id($ticketId);
        } else {
            $query->orderId($order->id);
        }

        $purchasedTickets = $query->all();

        $pdf = null;

        // Switch to use the correct site/language
        LocaleHelper::switchAppLanguage($site->language, null, function() use (&$pdf, $purchasedTickets, $order, $lineItem, $option) {
            $pdf = Events::$plugin->getPdf()->renderPdf($purchasedTickets, $order, $lineItem, $option);
        });

        $filenameFormat = $settings->ticketPdfFilenameFormat;
        $fileName = $this->getView()->renderObjectTemplate($filenameFormat, $order);

        if (!$fileName) {
            if ($order) {
                $fileName = 'Ticket-' . $order->number;
            } else {
                $fileName = 'Ticket';
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
