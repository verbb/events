<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\Ticket;

use Craft;
use craft\web\Controller;

use craft\commerce\Plugin as Commerce;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PurchasedTicketsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireCpRequest();

        return true;
    }

    public function actionIndex(): Response
    {
        $this->requirePermission('events-viewPurchasedTickets');

        return $this->renderTemplate('events/purchased-tickets');
    }

    public function actionEdit(int $purchasedTicketId = null, PurchasedTicket $purchasedTicket = null): Response
    {
        $this->requirePermission('events-editPurchasedTickets');

        $variables = [
            'purchasedTicketId' => $purchasedTicketId,
            'purchasedTicket' => $purchasedTicket,
            'brandNewPurchasedTicket' => false,
        ];

        if (empty($variables['purchasedTicket'])) {
            if (!empty($variables['purchasedTicketId'])) {
                $variables['purchasedTicket'] = Events::$plugin->getPurchasedTickets()->getPurchasedTicketById($purchasedTicketId);

                if (!$variables['purchasedTicket']) {
                    throw new NotFoundHttpException('No purchased ticket found.');
                }
            } else {
                $variables['purchasedTicket'] = new PurchasedTicket();
                $variables['brandNewPurchasedTicket'] = true;
            }
        }

        $this->_requireCanSave($variables['purchasedTicket']);

        if (!empty($variables['purchasedTicketId'])) {
            $variables['title'] = $variables['purchasedTicket']->title;
        } else {
            $variables['title'] = Craft::t('events', 'Create a Purchased Ticket');
        }

        $variables['fieldLayout'] = $variables['purchasedTicket']->getFieldLayout();

        return $this->renderTemplate('events/purchased-tickets/_edit', $variables);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission('events-editPurchasedTickets');

        $purchasedTicketId = $this->_positiveIntegerBodyParam('id');

        if ($purchasedTicketId) {
            $purchasedTicket = $this->_purchasedTicket($purchasedTicketId);
        } else {
            $purchasedTicket = new PurchasedTicket();
        }

        $this->_requireCanSave($purchasedTicket);

        $purchasedTicket->id = $purchasedTicketId;
        $purchasedTicket->enabled = $this->request->getParam('enabled', $purchasedTicket->enabled);
        $purchasedTicket->checkedIn = $this->request->getParam('checkedIn', $purchasedTicket->checkedIn);
        $purchasedTicket->checkedInDate = $this->request->getParam('checkedInDate', $purchasedTicket->checkedInDate);

        if (($ticketId = $this->_positiveIntegerBodyParam('ticketId')) !== null) {
            $ticket = Ticket::find()
                ->id($ticketId)
                ->status(null)
                ->one();

            if (!$ticket) {
                throw new BadRequestHttpException('Invalid ticket ID.');
            }

            $purchasedTicket->ticketId = $ticketId;
            $purchasedTicket->setTicket($ticket);
        }

        if (($orderId = $this->_positiveIntegerBodyParam('orderId')) !== null) {
            $order = Commerce::getInstance()->getOrders()->getOrderById($orderId);

            if (!$order) {
                throw new BadRequestHttpException('Invalid order ID.');
            }

            $purchasedTicket->orderId = $orderId;
            $purchasedTicket->setOrder($order);
        }

        // Update the relations, just in case
        if ($ticket = $purchasedTicket->getTicket()) {
            $purchasedTicket->eventId = $ticket->eventId;
            $purchasedTicket->sessionId = $ticket->sessionId;
            $purchasedTicket->ticketTypeId = $ticket->typeId;
        }

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

    public function actionDelete(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission('events-deletePurchasedTickets');

        $purchasedTicketId = $this->_requiredPositiveIntegerBodyParam('id');
        $purchasedTicket = $this->_purchasedTicket($purchasedTicketId);
        $this->_requireCanDelete($purchasedTicket);

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

    public function actionCheckIn(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission('events-editPurchasedTickets');

        $purchasedTicketId = $this->_requiredPositiveIntegerBodyParam('id');
        $purchasedTicket = $this->_purchasedTicket($purchasedTicketId);
        $this->_requireCanSave($purchasedTicket);

        if (!$purchasedTicket->getIsActive()) {
            Craft::$app->getSession()->setError(Craft::t('events', 'Cancelled tickets cannot be checked in.'));

            return $this->redirectToPostedUrl($purchasedTicket);
        }

        // Save any custom fields
        $purchasedTicket->setFieldValuesFromRequest('fields');

        if (!Craft::$app->getElements()->saveElement($purchasedTicket)) {
            Craft::$app->getSession()->setError(Craft::t('events', 'Couldn’t save purchased ticket.'));

            // Send the purchasedTicket back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'purchasedTicket' => $purchasedTicket,
            ]);

            return null;
        }

        Events::$plugin->getPurchasedTickets()->checkInPurchasedTicket($purchasedTicket);

        Craft::$app->getSession()->setNotice(Craft::t('events', 'Ticket checked in.'));

        return $this->redirectToPostedUrl($purchasedTicket);
    }

    public function actionCheckOut(): Response
    {
        $this->requirePostRequest();
        $this->requirePermission('events-editPurchasedTickets');

        $purchasedTicketId = $this->_requiredPositiveIntegerBodyParam('id');
        $purchasedTicket = $this->_purchasedTicket($purchasedTicketId);
        $this->_requireCanSave($purchasedTicket);

        Events::$plugin->getPurchasedTickets()->checkOutPurchasedTicket($purchasedTicket);

        Craft::$app->getSession()->setNotice(Craft::t('events', 'Ticket checked out.'));

        return $this->redirectToPostedUrl($purchasedTicket);
    }

    public function actionCancel(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission('events-editPurchasedTickets');

        $purchasedTicketId = $this->_requiredPositiveIntegerBodyParam('id');
        $purchasedTicket = $this->_purchasedTicket($purchasedTicketId);
        $this->_requireCanSave($purchasedTicket);

        $reason = $this->request->getBodyParam('reason');

        if (!Events::$plugin->getPurchasedTickets()->cancelPurchasedTicket($purchasedTicket, $reason)) {
            Craft::$app->getSession()->setError(Craft::t('events', 'Couldn’t cancel purchased ticket.'));

            return $this->redirectToPostedUrl($purchasedTicket);
        }

        Craft::$app->getSession()->setNotice(Craft::t('events', 'Purchased ticket cancelled.'));

        return $this->redirectToPostedUrl($purchasedTicket);
    }

    public function actionRestore(): ?Response
    {
        $this->requirePostRequest();
        $this->requirePermission('events-editPurchasedTickets');

        $purchasedTicketId = $this->_requiredPositiveIntegerBodyParam('id');
        $purchasedTicket = $this->_purchasedTicket($purchasedTicketId);
        $this->_requireCanSave($purchasedTicket);

        if (!Events::$plugin->getPurchasedTickets()->restorePurchasedTicket($purchasedTicket)) {
            Craft::$app->getSession()->setError(Craft::t('events', 'Couldn’t restore purchased ticket.'));

            return $this->redirectToPostedUrl($purchasedTicket);
        }

        Craft::$app->getSession()->setNotice(Craft::t('events', 'Purchased ticket restored.'));

        return $this->redirectToPostedUrl($purchasedTicket);
    }


    // Private Methods
    // =========================================================================

    private function _purchasedTicket(int $id): PurchasedTicket
    {
        $purchasedTicket = PurchasedTicket::find()
            ->id($id)
            ->status(null)
            ->one();

        if (!$purchasedTicket) {
            throw new NotFoundHttpException('No purchased ticket found.');
        }

        return $purchasedTicket;
    }

    private function _requireCanSave(PurchasedTicket $purchasedTicket): void
    {
        if (!Craft::$app->getElements()->canSave($purchasedTicket)) {
            throw new ForbiddenHttpException('User not authorized to edit this purchased ticket.');
        }
    }

    private function _requireCanDelete(PurchasedTicket $purchasedTicket): void
    {
        if (!Craft::$app->getElements()->canDelete($purchasedTicket)) {
            throw new ForbiddenHttpException('User not authorized to delete this purchased ticket.');
        }
    }

    private function _requiredPositiveIntegerBodyParam(string $name): int
    {
        $value = $this->_positiveIntegerBodyParam($name);

        if ($value === null) {
            throw new BadRequestHttpException("Missing required parameter: $name");
        }

        return $value;
    }

    private function _positiveIntegerBodyParam(string $name): ?int
    {
        $value = $this->request->getBodyParam($name);

        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        if (is_array($value)) {
            if (count($value) !== 1) {
                throw new BadRequestHttpException("Invalid $name.");
            }

            $value = reset($value);
        }

        if (!is_int($value) && !is_string($value)) {
            throw new BadRequestHttpException("Invalid $name.");
        }

        $value = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
            ],
        ]);

        if ($value === false) {
            throw new BadRequestHttpException("Invalid $name.");
        }

        return $value;
    }
}
