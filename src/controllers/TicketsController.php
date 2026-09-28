<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\PurchasedTicket;
use verbb\events\models\Settings;

use Craft;
use craft\helpers\Db;
use craft\helpers\StringHelper;
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

        $uid = $this->request->getBodyParam('uid', $this->request->getQueryParam('uid'));

        if ($settings->checkinLogin && !Craft::$app->getUser()->checkPermission('events-checkInTickets')) {
            return $this->_handleResponse([
                'error' => Craft::t('events', 'You do not have permission to check in tickets.'),
            ]);
        }

        if (!is_string($uid) || !StringHelper::isUUID($uid)) {
            return $this->_handleResponse([
                'error' => Craft::t('events', 'Could not find ticket SKU.'),
            ]);
        }

        $purchasedTicket = PurchasedTicket::find()->uid(Db::escapeParam($uid))->one();

        if (!$purchasedTicket) {
            return $this->_handleResponse([
                'error' => Craft::t('events', 'Could not find ticket SKU.'),
            ]);
        }

        if (!$purchasedTicket->getIsActive()) {
            return $this->_handleResponse([
                'error' => Craft::t('events', 'This ticket has been cancelled.'),
            ]);
        }

        if ($purchasedTicket->checkedIn) {
            return $this->_handleResponse([
                'error' => Craft::t('events', 'Ticket already checked in.'),
            ]);
        }

        if ($this->request->getBodyParam('confirm')) {
            $this->requirePostRequest();

            if (!Events::$plugin->getPurchasedTickets()->checkInPurchasedTicket($purchasedTicket)) {
                $purchasedTicket = PurchasedTicket::find()
                    ->id($purchasedTicket->id)
                    ->status(null)
                    ->one();

                if ($purchasedTicket?->checkedIn) {
                    $error = Craft::t('events', 'Ticket already checked in.');
                } elseif ($purchasedTicket && !$purchasedTicket->getIsActive()) {
                    $error = Craft::t('events', 'This ticket has been cancelled.');
                } else {
                    $error = Craft::t('events', 'Couldn’t check in purchased ticket.');
                }

                return $this->_handleResponse([
                    'error' => $error,
                ]);
            }

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
