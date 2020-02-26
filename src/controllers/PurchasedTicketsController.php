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
		
		$variables['fieldLayout'] = $variables['purchasedTicket']->getFieldLayout();
        
        return $this->renderTemplate('events/purchased-tickets/_edit', $variables);
    }

    public function actionSave()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $purchasedTicketId = $request->getParam('id');

        if ($purchasedTicketId) {
            $purchasedTicket = Events::getInstance()->getPurchasedTickets()->getPurchasedTicketById($purchasedTicketId);
        } else {
            $purchasedTicket = new PurchasedTicket();
        }

        $purchasedTicket->id = $purchasedTicketId;
		$purchasedTicket->ticketSku = $request->getParam('ticketSku');
		
		$purchasedTicket->setFieldValuesFromRequest('fields');

        // Save it
        if (!Craft::$app->getElements()->saveElement($purchasedTicket)) {
            Craft::$app->getSession()->setError(Craft::t('events', 'Couldn’t save purchased ticket.'));

            // Send the purchasedTicket back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'purchasedTicket' => $purchasedTicket,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('events', 'Purchased ticket saved.'));

        return $this->redirectToPostedUrl($purchasedTicket);
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();

        $purchasedTicketId = Craft::$app->getRequest()->getRequiredParam('id');
        $purchasedTicket = PurchasedTicket::findOne($purchasedTicketId);

        if (!$purchasedTicket) {
            throw new Exception(Craft::t('events', 'No purchased ticket exists with the ID “{id}”.', ['id' => $purchasedTicketId]));
        }

        if (!Craft::$app->getElements()->deleteElement($purchasedTicket)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('events', 'Couldn’t delete purchased ticket.'));
            Craft::$app->getUrlManager()->setRouteParams([
                'purchasedTicket' => $purchasedTicket,
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('events', 'Purchased ticket deleted.'));

        return $this->redirectToPostedUrl($purchasedTicket);
	}
	
	public function actionCheckin()
	{
		$this->requirePostRequest();

		$purchasedTicketId = Craft::$app->getRequest()->getRequiredParam('id');
		$purchasedTicket = PurchasedTicket::findOne($purchasedTicketId);
		
		if (!$purchasedTicket) {
            throw new Exception(Craft::t('events', 'No purchased ticket exists with the ID “{id}”.', ['id' => $purchasedTicketId]));
        }

		Events::$plugin->getPurchasedTickets()->checkInPurchasedTicket($purchasedTicket);

		Craft::$app->getSession()->setNotice(Craft::t('events', 'Ticket checked in.'));

        return $this->redirectToPostedUrl($purchasedTicket);
	}

	public function actionUncheckin()
	{
		$this->requirePostRequest();

		$purchasedTicketId = Craft::$app->getRequest()->getRequiredParam('id');
		$purchasedTicket = PurchasedTicket::findOne($purchasedTicketId);
		
		if (!$purchasedTicket) {
            throw new Exception(Craft::t('events', 'No purchased ticket exists with the ID “{id}”.', ['id' => $purchasedTicketId]));
        }

		Events::$plugin->getPurchasedTickets()->unCheckInPurchasedTicket($purchasedTicket);

		Craft::$app->getSession()->setNotice(Craft::t('events', 'Ticket un-checked in.'));

        return $this->redirectToPostedUrl($purchasedTicket);
	}
}
