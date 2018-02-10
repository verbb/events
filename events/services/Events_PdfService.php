<?php

namespace Craft;

class Events_PdfService extends BaseApplicationComponent
{
    /**
     * Get the URL to the voucher codes’s PDF file.
     *
     * @param string   $orderNumber
     * @param int|null $lineItemId
     *
     * @return false|string
     * @throws Exception
     */
    public function getPdfUrl($orderNumber, $lineItemId = null)
    {
        $url = false;

        // Make sure the template exists
        $template = EventsHelper::getPlugin()->getSettings()->ticketsPdfPath;

        if (!$template) {
            return false;
        }

        // Check if voucher codes where purchased in this order
        $order = craft()->commerce_orders->getOrderByNumber($orderNumber);

        $attributes = [
            'orderId' => $order->id,
        ];

        if ($lineItemId) {
            $attributes['lineItemId'] = $lineItemId;
        }

        $tickets = EventsHelper::getPurchasedTicketsService()->getAllByAttributes($attributes);

        if (!$tickets) {
            return false;
        }

        // Set Craft to the site template mode
        $templatesService = craft()->templates;
        $oldTemplateMode = $templatesService->getTemplateMode();
        $templatesService->setTemplateMode(TemplateMode::Site);

        if ($templatesService->doesTemplateExist($template)) {
            $lineItemParam = '';

            if ($lineItemId) {
                $lineItemParam = "&lineItemId={$lineItemId}";
            }

            $url = UrlHelper::getActionUrl("events/downloads/pdf?number={$orderNumber}" . $lineItemParam);
        }

        // Restore the original template mode
        $templatesService->setTemplateMode($oldTemplateMode);

        return $url;
    }
}