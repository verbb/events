<?php

namespace Craft;

use Dompdf\Dompdf;
use Dompdf\Options;

class Events_DownloadsController extends BaseController
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    /**
     * @throws Exception
     * @throws HttpException
     */
    public function actionPdf()
    {
        $template = EventsHelper::getPlugin()->getSettings()->ticketsPdfPath;
        $filenameFormat = EventsHelper::getPlugin()->getSettings()->ticketsPdfFilenameFormat;

        $format = craft()->request->getParam('format');
        $attach = craft()->request->getParam('attach');

        // Set Craft to the site template mode
        $templatesService = craft()->templates;
        $oldTemplateMode = $templatesService->getTemplateMode();
        $templatesService->setTemplateMode(TemplateMode::Site);

        if (!$template || !$templatesService->doesTemplateExist($template)) {
            // Restore the original template mode
            $templatesService->setTemplateMode($oldTemplateMode);

            throw new HttpException(404, 'Template does not exist.');
        }

        $number = craft()->request->getQuery('number');
        $lineItemId = craft()->request->getQuery('lineItemId');
        $order = craft()->commerce_orders->getOrderByNumber($number);

        $attributes = [
            'orderId' => $order->id
        ];

        if ($lineItemId) {
            $attributes['lineItemId'] = $lineItemId;
        }

        $tickets = EventsHelper::getPurchasedTicketsService()->getAllByAttributes($attributes);

        if (!$tickets) {
            throw new HttpException(404, 'No Tickets exist.');
        }

        $fileName = craft()->templates->renderObjectTemplate($filenameFormat, $order);

        if (!$fileName) {
            $fileName = 'Tickets-' . $order->number;
        }

        $html = $templatesService->render($template, [
            'tickets' => $tickets,
            'order' => $order,
        ]);

        // Set the config options
        $pathService = craft()->path;
        $dompdfTempDir = $pathService->getTempPath() . 'events_dompdf';
        $dompdfFontCache = $pathService->getCachePath() . 'events_dompdf';
        $dompdfLogFile = $pathService->getLogPath() . 'events_dompdf.htm';
        IOHelper::ensureFolderExists($dompdfTempDir);
        IOHelper::ensureFolderExists($dompdfFontCache);

        $isRemoteEnabled = craft()->config->get('pdfAllowRemoteImages', 'events');

        $options = new Options([
            'tempDir'         => $dompdfTempDir,
            'fontCache'       => $dompdfFontCache,
            'logOutputFile'   => $dompdfLogFile,
            'isRemoteEnabled' => $isRemoteEnabled,
            'fontDir'         => $dompdfFontCache,
        ]);

        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html);

        // Set the paper size/orientation
        $size = craft()->config->get('pdfPaperSize', 'events');
        $orientation = craft()->config->get('pdfPaperOrientation', 'events');
        $dompdf->setPaper($size, $orientation);

        $streamOptions = array();

        if ($attach) {
            $streamOptions = array('Attachment' => false);
        }

        if ($format == 'plain') {
            echo $html;
        } else {
            $dompdf->render();
            $dompdf->stream($fileName . '.pdf', $streamOptions);
        }

        // Restore the original template mode
        $templatesService->setTemplateMode($oldTemplateMode);

        craft()->end();
    }
}