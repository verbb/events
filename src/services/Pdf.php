<?php
namespace verbb\events\services;

use verbb\events\Events;
use verbb\events\models\Settings;
use verbb\events\events\PdfEvent;
use verbb\events\events\PdfRenderOptionsEvent;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\web\View;

use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;

use Dompdf\Dompdf;
use Dompdf\Options;

use yii\base\Component;
use yii\base\ErrorException;
use yii\base\Exception;

class Pdf extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_BEFORE_RENDER_PDF = 'beforeRenderPdf';
    public const EVENT_AFTER_RENDER_PDF = 'afterRenderPdf';
    public const EVENT_MODIFY_RENDER_OPTIONS = 'modifyRenderOptions';


    // Public Methods
    // =========================================================================

    public function getPdfUrl(Order $order, LineItem $lineItem = null, ?string $option = null): string
    {
        $currentSite = Craft::$app->getSites()->getCurrentSite();

        return UrlHelper::actionUrl('events/downloads/pdf', array_filter([
            'number' => $order->number ?? null,
            'option' => $option ?? null,
            'lineItemId' => $lineItem->id ?? null,
            'site' => $currentSite->handle,
        ]));
    }

    public function getPdfUrlForTicket(Ticket $ticket, ?string $option = null): string
    {
        $currentSite = Craft::$app->getSites()->getCurrentSite();
        
        return UrlHelper::actionUrl('events/downloads/pdf', array_filter([
            'ticketId' => $ticket->id ?? null,
            'option' => $option ?? null,
            'site' => $currentSite->handle,
        ]));
    }

    public function renderPdf(array $tickets, Order $order = null, ?LineItem $lineItem = null, ?string $option = '', ?string $templatePath = null): string
    {
        /* @var Settings $settings */
        $settings = Events::$plugin->getSettings();
        $format = null;

        $request = Craft::$app->getRequest();

        if (!$request->getIsConsoleRequest()) {
            $format = $request->getParam('format');
        }

        if (!$templatePath) {
            $templatePath = $settings->ticketPdfPath;
        }

        $variables = compact('order', 'tickets', 'lineItem', 'option');

        // Trigger a 'beforeRenderPdf' event
        $event = new PdfEvent([
            'order' => $order,
            'option' => $option,
            'template' => $templatePath,
            'variables' => $variables,
        ]);
        $this->trigger(self::EVENT_BEFORE_RENDER_PDF, $event);

        if ($event->pdf !== null) {
            return $event->pdf;
        }

        $variables = $event->variables;
        $variables['order'] = $event->order;
        $variables['option'] = $event->option;

        // Set Craft to the site template mode
        $view = Craft::$app->getView();
        $oldTemplateMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);

        if (!$event->template || !$view->doesTemplateExist($event->template)) {
            // Restore the original template mode
            $view->setTemplateMode($oldTemplateMode);

            throw new Exception('PDF template file does not exist.');
        }

        try {
            $html = $view->renderTemplate($templatePath, $variables);
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
            throw new ErrorException("Unable to write to file: $dompdfLogFile");
        }

        if (!FileHelper::isWritable($dompdfFontCache)) {
            throw new ErrorException("Unable to write to folder: $dompdfFontCache");
        }

        if (!FileHelper::isWritable($dompdfTempDir)) {
            throw new ErrorException("Unable to write to folder: $dompdfTempDir");
        }

        $isRemoteEnabled = $settings->pdfAllowRemoteImages;

        $options = new Options();
        $options->setTempDir($dompdfTempDir);
        $options->setFontCache($dompdfFontCache);
        $options->setLogOutputFile($dompdfLogFile);
        $options->setIsRemoteEnabled($isRemoteEnabled);

        // Set additional render options
        if ($this->hasEventHandlers(self::EVENT_MODIFY_RENDER_OPTIONS)) {
            $this->trigger(self::EVENT_MODIFY_RENDER_OPTIONS, new PdfRenderOptionsEvent([
                'options' => $options,
            ]));
        }

        $dompdf->setOptions($options);

        // Paper Size and Orientation
        $pdfPaperSize = $settings->pdfPaperSize;
        $pdfPaperOrientation = $settings->pdfPaperOrientation;
        $dompdf->setPaper($pdfPaperSize, $pdfPaperOrientation);

        $dompdf->loadHtml($html);

        if ($format === 'plain') {
            return $html;
        }

        $dompdf->render();

        // Trigger an 'afterRenderPdf' event
        $event = new PdfEvent([
            'order' => $event->order,
            'option' => $event->option,
            'template' => $event->template,
            'variables' => $variables,
            'pdf' => $dompdf->output(),
        ]);
        $this->trigger(self::EVENT_AFTER_RENDER_PDF, $event);

        return $event->pdf;
    }
}