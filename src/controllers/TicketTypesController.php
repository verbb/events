<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;
// use verbb\events\models\TicketTypeSite;

use Craft;
use craft\web\Controller;

use yii\base\Exception;
use yii\web\Response;

class TicketTypesController extends Controller
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->requirePermission('events-manageTicketTypes');

        parent::init();
    }

    public function actionEdit(int $ticketTypeId = null, TicketType $ticketType = null): Response
    {
        $variables = [
            'ticketTypeId' => $ticketTypeId,
            'ticketType' => $ticketType,
            'brandNewTicketType' => false,
        ];

        if (empty($variables['ticketType'])) {
            if (!empty($variables['ticketTypeId'])) {
                $ticketTypeId = $variables['ticketTypeId'];
                $variables['ticketType'] = Events::getInstance()->getTicketTypes()->getTicketTypeById($ticketTypeId);

                if (!$variables['ticketType']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['ticketType'] = new TicketType();
                $variables['brandNewTicketType'] = true;
            }
        }

        if (!empty($variables['ticketTypeId'])) {
            $variables['title'] = $variables['ticketType']->name;
        } else {
            $variables['title'] = Craft::t('events', 'Create a Ticket Type');
        }
        
        return $this->renderTemplate('events/ticket-types/_edit', $variables);
    }

    public function actionSave()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $ticketTypeId = $request->getParam('ticketTypeId');

        if ($ticketTypeId) {
            $ticketType = Events::getInstance()->getTicketTypes()->getTicketTypeById($ticketTypeId);
        } else {
            $ticketType = new TicketType();
        }

        $ticketType->id = $ticketTypeId;
        $ticketType->title = $request->getParam('name');
        $ticketType->handle = $request->getParam('handle');
        $ticketType->taxCategoryId = $request->getParam('taxCategoryId');
        $ticketType->shippingCategoryId = $request->getParam('shippingCategoryId');

        // Set the ticket type field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Ticket::class;
        $ticketType->setFieldLayout($fieldLayout);

        // Save it
        if (!Craft::$app->getElements()->saveElement($ticketType)) {
            Craft::$app->getSession()->setError(Craft::t('events', 'Couldn’t save ticket type.'));

            // Send the ticketType back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'ticketType' => $ticketType,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('events', 'Ticket type saved.'));

        return $this->redirectToPostedUrl($ticketType);
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $ticketTypeId = Craft::$app->getRequest()->getRequiredParam('id');
        $ticketType = TicketType::findOne($ticketTypeId);

        if (!$ticketType) {
            throw new Exception(Craft::t('events', 'No ticket type exists with the ID “{id}”.', ['id' => $ticketTypeId]));
        }

        if (!Craft::$app->getElements()->deleteElement($ticketType)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('events', 'Couldn’t delete ticket type.'));
            Craft::$app->getUrlManager()->setRouteParams([
                'ticketType' => $ticketType,
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            return $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('events', 'Ticket Type deleted.'));

        return $this->redirectToPostedUrl($ticketType);
    }

}
