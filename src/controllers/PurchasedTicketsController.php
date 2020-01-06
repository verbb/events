<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\elements\PurchasedTicket;
use verbb\events\helpers\EventHelper;
use verbb\events\helpers\TicketHelper;

use Craft;
use craft\base\Element;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\Localization;
use craft\helpers\UrlHelper;
use craft\models\Site;
use craft\web\Controller;

use yii\base\Exception;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class PurchasedTicketsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        return $this->renderTemplate('events/purchased-tickets');
    }

    public function actionEdit(int $purchasedTicketId = null, PurchasedTicket $purchasedTicket = null): Response
    {
        $variables = [
            'purchasedTicketId' => $purchasedTicketId,
            'purchasedTicket' => $purchasedTicket,
            'brandNewPurchasedTicket' => false,
        ];

        if (empty($variables['purchasedTicket'])) {
            if (!empty($variables['purchasedTicketId'])) {
                $purchasedTicketId = $variables['purchasedTicketId'];
                $variables['purchasedTicket'] = Events::getInstance()->getPurchasedTickets()->getPurchasedTicketById($purchasedTicketId);

                if (!$variables['purchasedTicket']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['purchasedTicket'] = new PurchasedTicket();
                $variables['brandNewPurchasedTicket'] = true;
            }
        }

        if (!empty($variables['purchasedTicketId'])) {
            $variables['title'] = $variables['purchasedTicket']->ticketSku;
        } else {
            $variables['title'] = Craft::t('events', 'Create a Purchased Ticket');
        }
        
        return $this->renderTemplate('events/purchased-tickets/_edit', $variables);
    }
}
