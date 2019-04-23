<?php
namespace verbb\events\services;

use verbb\events\Events;
use verbb\events\elements\Ticket;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\web\View;

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use craft\commerce\events\PdfEvent;
use craft\commerce\models\LineItem;

use Dompdf\Dompdf;
use Dompdf\Options;

use yii\base\Component;
use yii\base\Exception;

class Pdf extends Component
{
    // Constants
    // =========================================================================

    const EVENT_BEFORE_RENDER_PDF = 'beforeRenderPdf';
    const EVENT_AFTER_RENDER_PDF = 'afterRenderPdf';

    // Public Methods
    // =========================================================================

    public function getPdfUrl(Order $order, LineItem $lineItem = null, $option = null)
    {
        $url = null;

        try {
            $path = "events/downloads/pdf?number={$order->number}" . ($option ? "&option={$option}" : '') . ($lineItem ? "&lineItemId={$lineItem->id}" : '');
            $path = Craft::$app->getConfig()->getGeneral()->actionTrigger . '/' . trim($path, '/');
            $url = UrlHelper::siteUrl($path);
        } catch (\Exception $e) {
            Craft::error($e->getMessage());
            return null;
        }

        return $url;
    }

    public function getPdfUrlForTicket($ticket, $option = null)
    {
        $url = null;

        try {
            $path = "events/downloads/pdf?ticketId={$ticket->id}" . ($option ? "&option={$option}" : '');
            $path = Craft::$app->getConfig()->getGeneral()->actionTrigger . '/' . trim($path, '/');
            $url = UrlHelper::siteUrl($path);
        } catch (\Exception $e) {
            Craft::error($e->getMessage());
            return null;
        }

        return $url;
    }

    public function renderPdf($tickets, $order = [], $lineItem = null, $option = '', $templatePath = null): string
    {
        $settings = Events::getInstance()->getSettings();

        $request = Craft::$app->getRequest();
        $format = $request->getParam('format');

        if (null === $templatePath){
            $templatePath = $settings->ticketPdfPath;
        }

        // Trigger a 'beforeRenderPdf' event
        $event = new PdfEvent([
            'order' => $order,
            'option' => $option,
            'template' => $templatePath,
        ]);
        $this->trigger(self::EVENT_BEFORE_RENDER_PDF, $event);

        if ($event->pdf !== null) {
            return $event->pdf;
        }

        // Set Craft to the site template mode
        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

        if (!$templatePath || !$view->doesTemplateExist($templatePath)) {
            // Restore the original template mode
            $view->setTemplateMode($oldTemplateMode);

            throw new Exception('PDF template file does not exist.');
        }

        try {
            $html = $view->renderTemplate($templatePath, compact('order', 'tickets', 'lineItem', 'option'));
        } catch (\Exception $e) {
            // Set the pdf html to the render error.
            if ($order) {
                Craft::error('Ticket PDF render error. Order number: ' . $order->getShortNumber() . '. ' . $e->getMessage());
            }

            if ($tickets) {
                Craft::error('Ticket PDF render error. Ticket key: ' . $tickets[0]->ticketSku . '. ' . $e->getMessage());
            }

            Craft::$app->getErrorHandler()->logException($e);
            $html = Craft::t('events', 'An error occurred while generating this PDF.');
        }

        // Restore the original template mode
        $view->setTemplateMode($oldTemplateMode);

        $dompdf = new Dompdf();

        // Set the config options
        $pathService = Craft::$app->getPath();
        $dompdfTempDir = $pathService->getTempPath() . DIRECTORY_SEPARATOR . 'events_dompdf';
        $dompdfFontCache = $pathService->getCachePath() . DIRECTORY_SEPARATOR . 'events_dompdf';
        $dompdfLogFile = $pathService->getLogPath() . DIRECTORY_SEPARATOR . 'events_dompdf.htm';

        // Should throw an error if not writable
        FileHelper::isWritable($dompdfTempDir);
        FileHelper::isWritable($dompdfLogFile);

        $isRemoteEnabled = $settings->pdfAllowRemoteImages;

        $options = new Options();
        $options->setTempDir($dompdfTempDir);
        $options->setFontCache($dompdfFontCache);
        $options->setLogOutputFile($dompdfLogFile);
        $options->setIsRemoteEnabled($isRemoteEnabled);

        // Paper Size and Orientation
        $pdfPaperSize = $settings->pdfPaperSize;
        $pdfPaperOrientation = $settings->pdfPaperOrientation;
        $dompdf->setPaper($pdfPaperSize, $pdfPaperOrientation);

        $dompdf->setOptions($options);

        $dompdf->loadHtml($html);

        if ($format === 'plain') {
            return $html;
        } else {
            $dompdf->render();
        }

        // Trigger an 'afterRenderPdf' event
        $event = new PdfEvent([
            'order' => $order,
            'option' => $option,
            'template' => $templatePath,
            'pdf' => $dompdf->output(),
        ]);
        $this->trigger(self::EVENT_AFTER_RENDER_PDF, $event);

        return $event->pdf;
    }
}