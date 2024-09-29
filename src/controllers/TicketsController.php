<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\PurchasedTicket;
use verbb\events\models\Settings;

use Craft;
use craft\web\Controller;
use craft\web\View;

use yii\web\Response;

class TicketsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionCheckIn(array $variables = []): Response|string
    {
        /* @var Settings $settings */
        $settings = Events::$plugin->getSettings();

        $uid = $this->request->getRequiredParam('uid');

        if ($settings->checkinLogin && !Craft::$app->getUser()->checkPermission('events-checkInTickets')) {
            return $this->_handleResponse([
                'error' => Craft::t('events', 'You do not have permission to check in tickets.'),
            ]);
        }

        $purchasedTicket = PurchasedTicket::find()->uid($uid)->one();

        if (!$purchasedTicket) {
            return $this->_handleResponse([
                'error' => Craft::t('events', 'Could not find ticket SKU.'),
            ]);
        }

        if ($purchasedTicket->checkedIn) {
            return $this->_handleResponse([
                'error' => Craft::t('events', 'Ticket already checked in.'),
            ]);
        }

        if ($this->request->getParam('confirm')) {
            Events::$plugin->getPurchasedTickets()->checkInPurchasedTicket($purchasedTicket);

            return $this->_handleResponse([
                'success' => true,
                'purchasedTicket' => $purchasedTicket,
            ]);
        }

        return $this->_handleResponse([
            'purchasedTicket' => $purchasedTicket,
        ]);
    }

    // Private Methods
    // =========================================================================

    private function _handleResponse(array $variables): Response|string
    {
        /* @var Settings $settings */
        $settings = Events::$plugin->getSettings();

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson($variables);
        }

        $user = Craft::$app->getUser();

        $oldMode = Craft::$app->getView()->getTemplateMode();
        $templateMode = View::TEMPLATE_MODE_CP;
        $template = 'events/check-in';

        if ($settings->checkinTemplate) {
            $templateMode = View::TEMPLATE_MODE_SITE;
            $template = $settings->checkinTemplate;
        }

        return Craft::$app->getView()->renderTemplate($template, $variables, $templateMode);
    }
}
