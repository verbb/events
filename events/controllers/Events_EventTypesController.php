<?php
namespace Craft;

class Events_EventTypesController extends BaseController
{

    // Public Methods
    // =========================================================================

    public function init()
    {
        if (!craft()->userSession->checkPermission('events-manageEventTypes')) {
            throw new HttpException(403, Craft::t('You don\'t have permissions to do that.'));
        }

        parent::init();
    }

    public function actionEdit(array $variables = array())
    {
        if (empty($variables['eventType'])) {
            if (empty($variables['eventTypeId'])) {
                $eventType = new Events_EventTypeModel();
            } else {
                $eventType = EventsHelper::getEventTypesService()->getEventTypeById($variables['eventTypeId']);
            }

            if (!$eventType) {
                $eventType = new Events_EventTypeModel();
            }

            $variables['eventType'] = $eventType;
        }
        
        $variables['title'] = empty($variables['eventType']->id) ? Craft::t("Create a new Event Type") : $variables['eventType']->name;

        $this->renderTemplate('events/eventtypes/_edit', $variables);
    }

    public function actionSave()
    {
        $this->requirePostRequest();

        $eventType = new Events_EventTypeModel();

        $eventType->id = craft()->request->getPost('eventTypeId');
        $eventType->name = craft()->request->getPost('name');
        $eventType->handle = craft()->request->getPost('handle');
        $eventType->hasUrls = craft()->request->getPost('hasUrls');
//        $eventType->skuFormat = craft()->request->getPost('skuFormat');
        $eventType->template = craft()->request->getPost('template');

        $locales = array();

        foreach (craft()->i18n->getSiteLocaleIds() as $localeId) {
            $locales[$localeId] = new Events_EventTypeLocaleModel(array(
                'locale' => $localeId,
                'urlFormat' => craft()->request->getPost('urlFormat.'.$localeId)
            ));
        }

        $eventType->setLocales($locales);

        $fieldLayout = craft()->fields->assembleLayoutFromPost();
        $fieldLayout->type = 'Events_Event';
        $eventType->asa('eventFieldLayout')->setFieldLayout($fieldLayout);

        // Save it
        if (EventsHelper::getEventTypesService()->saveEventType($eventType)) {
            craft()->userSession->setNotice(Craft::t('Event type saved.'));
            $this->redirectToPostedUrl($eventType);
        } else {
            craft()->userSession->setError(Craft::t('Couldn’t save event type.'));
        }

        // Send the eventType back to the template
        craft()->urlManager->setRouteVariables(array(
            'eventType' => $eventType
        ));
    }

    public function actionDelete()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $id = craft()->request->getRequiredPost('id');

        try {
            EventsHelper::getEventTypesService()->deleteEventTypeById($id);
            $this->returnJson(array('success' => true));
        } catch (Exception $e) {
            $this->returnErrorJson($e->getMessage());
        }
    }

}
