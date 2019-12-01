<?php
namespace verbb\events\controllers;

use verbb\events\Events;

use Craft;
use craft\web\Controller;
use craft\web\View;

class TicketController extends Controller
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionCheckin(array $variables = array())
    {
        $settings = Events::$plugin->getSettings();

        $sku = Craft::$app->request->getParam('sku');

        if (!$sku) {
            return $this->_handleResponse([
                'success' => false,
                'message' => Craft::t('events', 'Missing required ticket SKU.'),
            ]);
        }

        $purchasedTicket = Events::$plugin->getPurchasedTickets()->getPurchasedTicket([
            'ticketSku' => $sku,
        ]);

        if (!$purchasedTicket->id) {
            return $this->_handleResponse([
                'success' => false,
                'message' => Craft::t('events', 'Could not find ticket SKU.'),
            ]);
        }

        if ($purchasedTicket->checkedIn) {
            return $this->_handleResponse([
                'success' => false,
                'message' => Craft::t('events', 'Ticket already checked in.'),
            ]);
        }

        Events::$plugin->getPurchasedTickets()->checkInPurchasedTicket($purchasedTicket);

        return $this->_handleResponse([
            'success' => true,
            'purchasedTicket' => $purchasedTicket,
        ]);
    }

    // Private Methods
    // =========================================================================

    private function _handleResponse($variables)
    {
        $settings = Events::$plugin->getSettings();

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson($variables);
        }

        $oldMode = Craft::$app->view->getTemplateMode();
        $templateMode = View::TEMPLATE_MODE_CP;
        $template = 'events/check-in';

        if ($settings->checkinTemplate) {
            $templateMode = View::TEMPLATE_MODE_SITE;
            $template = $settings->checkinTemplate;
        }

        Craft::$app->view->setTemplateMode($templateMode);
        $html = Craft::$app->view->renderTemplate($template, $variables);
        Craft::$app->view->setTemplateMode($oldMode);

        return $html;
    }
}