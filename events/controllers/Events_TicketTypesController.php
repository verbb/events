<?php
namespace Craft;

class Events_TicketTypesController extends BaseController
{

    // Public Methods
    // =========================================================================

    public function init()
    {
        if (!craft()->userSession->checkPermission('events-manageTicketTypes')) {
            throw new HttpException(403, Craft::t('You don\'t have permissions to do that.'));
        }

        parent::init();
    }

    public function actionEdit(array $variables = array())
    {
        if (empty($variables['ticketType'])) {
            if (empty($variables['ticketTypeId'])) {
                $ticketType = new Events_TicketTypeModel();
            } else {
                $ticketType = EventsHelper::getTicketTypesService()->getTicketTypeById($variables['ticketTypeId']);
            }

            if (!$ticketType) {
                $ticketType = new Events_TicketTypeModel();
            }

            $variables['ticketType'] = $ticketType;
        }

        $variables['title'] = empty($variables['ticketType']->id) ? Craft::t("Create a new Ticket Type") : $variables['ticketType']->title;

        $this->renderTemplate('events/tickettypes/_edit', $variables);
    }

    public function actionSave()
    {
        $this->requirePostRequest();

        $ticketType = new Events_TicketTypeModel();

        $ticketType->id = craft()->request->getPost('ticketTypeId');
        $ticketType->getContent()->title = craft()->request->getPost('title');
        $ticketType->handle = craft()->request->getPost('handle');
        $ticketType->taxCategoryId = craft()->request->getPost('taxCategoryId');
        $ticketType->shippingCategoryId = craft()->request->getPost('shippingCategoryId');
//        $ticketType->hasUrls = craft()->request->getPost('hasUrls');
//        $ticketType->skuFormat = craft()->request->getPost('skuFormat');
//        $ticketType->template = craft()->request->getPost('template');

//        $locales = array();

//        foreach (craft()->i18n->getSiteLocaleIds() as $localeId) {
//            $locales[$localeId] = new Events_TicketTypeLocaleModel(array(
//                'locale' => $localeId,
//                'urlFormat' => craft()->request->getPost('urlFormat.'.$localeId)
//            ));
//        }
//
//        $ticketType->setLocales($locales);

        $fieldLayout = craft()->fields->assembleLayoutFromPost();
        $fieldLayout->type = 'Events_Ticket';
        $ticketType->asa('ticketFieldLayout')->setFieldLayout($fieldLayout);

        // Save it
        if (EventsHelper::getTicketTypesService()->saveTicketType($ticketType)) {
            craft()->userSession->setNotice(Craft::t('Ticket type saved.'));
            $this->redirectToPostedUrl($ticketType);
        } else {
            craft()->userSession->setError(Craft::t('Couldn’t save ticket type.'));
        }

        // Send the ticketType back to the template
        craft()->urlManager->setRouteVariables(array(
            'ticketType' => $ticketType
        ));
    }

    public function actionDelete()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $id = craft()->request->getRequiredPost('id');

        try {
            EventsHelper::getTicketTypesService()->deleteTicketTypeById($id);
            $this->returnJson(array('success' => true));
        } catch (Exception $e) {
            $this->returnErrorJson($e->getMessage());
        }
    }

}
