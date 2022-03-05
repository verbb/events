<?php
namespace verbb\events\services;

use verbb\events\Events;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\web\View;

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

    public function getPdfUrl(Order $order, LineItem $lineItem = null, $option = null): string
    {
        return UrlHelper::actionUrl('events/downloads/pdf', array_filter([
            'number' => $order->number ?? null,
            'option' => $option ?? null,
            'lineItemId' => $lineItem->id ?? null,
        ]));
    }

    public function getPdfUrlForTicket($ticket, $option = null): string
    {
        return UrlHelper::actionUrl('events/downloads/pdf', array_filter([
            'ticketId' => $ticket->id ?? null,
            'option' => $option ?? null,
        ]));
    }

    public function renderPdf($tickets, Order $order = null, $lineItem = null, $option = '', $templatePath = null): string
    {
        $settings = Events::$plugin->getSettings();

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

        // Ensure directories are created
        FileHelper::createDirectory($dompdfTempDir);
        FileHelper::createDirectory($dompdfFontCache);

        if (!FileHelper::isWritable($dompdfLogFile)) {
            throw new Exception("Unable to write to file: $dompdfLogFile");
        }

        if (!FileHelper::isWritable($dompdfFontCache)) {
            throw new Exception("Unable to write to folder: $dompdfFontCache");
        }

        if (!FileHelper::isWritable($dompdfTempDir)) {
            throw new Exception("Unable to write to folder: $dompdfTempDir");
        }

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
        }

        $dompdf->render();

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