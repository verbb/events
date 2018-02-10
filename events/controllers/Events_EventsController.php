<?php

namespace Craft;

class Events_EventsController extends BaseController
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = ['actionViewSharedEvent'];


    // Public Methods
    // =========================================================================

    public function actionIndex(array $variables = array())
    {
        $this->renderTemplate('events/events/index', $variables);
    }

    public function actionEdit(array $variables = array())
    {
        // Make sure a correct event type handle was passed so we can check permissions
        if (!empty($variables['eventTypeHandle'])) {
            $variables['eventType'] = EventsHelper::getEventTypesService()->getEventTypeByHandle($variables['eventTypeHandle']);
        }

        if (empty($variables['eventType'])) {
            throw new HttpException(404);
        }

        $this->_enforceEventPermissionsForEventType($variables['eventType']->id);

        $this->_prepareVariableArray($variables);
        $this->_maybeEnableLivePreview($variables);

        // Set up tickets
        if ($variables['event']->id) {
            $tickets = EventsHelper::getTicketsService()->getAllTicketsByEventId($variables['event']->id);
        } else {
            $tickets = null;
        }
        $variables['tickets'] = $tickets;
        $variables['ticketRowHtml'] = $this->_getTicketRowHtml($tickets);

        $this->renderTemplate('events/events/_edit', $variables);
    }

    public function actionDeleteEvent()
    {
        $this->requirePostRequest();

        $eventId = craft()->request->getRequiredPost('eventId');
        $event = EventsHelper::getEventsService()->getEventById($eventId);

        if (!$event) {
            throw new HttpException(404);
        }

        $this->_enforceEventPermissionsForEventType($event->typeId);

        if (EventsHelper::getEventsService()->deleteEvent($event)) {
            if (craft()->request->isAjaxRequest()) {
                $this->returnJson(['success' => true]);
            } else {
                craft()->userSession->setNotice(Craft::t('Event deleted.'));
                $this->redirectToPostedUrl($event);
            }
        } else {
            if (craft()->request->isAjaxRequest()) {
                $this->returnJson(['success' => false]);
            } else {
                craft()->userSession->setError(Craft::t('Couldn’t delete event.'));

                craft()->urlManager->setRouteVariables([
                    'event' => $event,
                ]);
            }
        }
    }

    public function actionSave()
    {
        $this->requirePostRequest();

        $event = $this->_buildEventFromPost();

        EventsHelper::getEventsService()->populateEventTicketModels($event, craft()->request->getPost('tickets'));

        $this->_enforceEventPermissionsForEventType($event->typeId);

        $existingEvent = (bool)$event->id;

        if (EventsHelper::getEventsService()->saveEvent($event)) {
            craft()->userSession->setNotice(Craft::t('Event saved.'));
            $this->redirectToPostedUrl($event);
        }

        if (!$existingEvent) {
            $event->id = null;
        }

        // Get ticket errors
        foreach ($event->getTickets() as $ticket) {
            foreach ($ticket->getAllErrors() as $error) {
                $event->addError('ticket', $error);
            }
        }

        craft()->userSession->setError(Craft::t('Couldn’t save event. ' . implode(' ', $event->getAllErrors())));
        craft()->urlManager->setRouteVariables(['event' => $event]);
    }

    public function actionPreviewEvent()
    {

        $this->requirePostRequest();

        $event = $this->_buildEventFromPost();
        $this->_enforceEventPermissionsForEventType($event->typeId);

        $this->_showEvent($event);
    }

    public function actionShareEvent($eventId, $locale = null)
    {
        $event = EventsHelper::getEventsService()->getEventById($eventId, $locale);

        if (!$event || !EventsHelper::getEventTypessService()->isEventTypeTemplateValid($event->getEventType())) {
            throw new HttpException(404);
        }

        $this->_enforceEventPermissionsForEventType($event->typeId);

        // Create the token and redirect to the event URL with the token in place
        $token = craft()->tokens->createToken([
            'action' => 'events/events/viewSharedEvent',
            'params' => [
                'eventId' => $eventId,
                'locale'  => $event->locale,
            ],
        ]);

        $url = UrlHelper::getUrlWithToken($event->getUrl(), $token);
        craft()->request->redirect($url);
    }

    public function actionViewSharedEvent($eventId, $locale = null)
    {
        $this->requireToken();

        $event = EventsHelper::getEventsService()->getEventById($eventId, $locale);

        if (!$event) {
            throw new HttpException(404);
        }

        $this->_showEvent($event);
    }

    // Private Methods
    // =========================================================================

    private function _showEvent(Events_EventModel $event)
    {
        $eventType = $event->getEventType();

        if (!$eventType) {
            throw new HttpException(404);
        }

        craft()->setLanguage($event->locale);

        // Have this event override any freshly queried events with the same ID/locale
        craft()->elements->setPlaceholderElement($event);

        craft()->templates->getTwig()->disableStrictVariables();

        $this->renderTemplate($eventType->template, ['event' => $event]);
    }

    private function _enforceEventPermissionsForEventType($eventTypeId)
    {
        // Check for general event commerce access
        if (!craft()->userSession->checkPermission('events-manageEvents')) {
            throw new HttpException(403, Craft::t('This action is not allowed for the current user.'));
        }

        // Check if the user can edit the events in the event type
        if (!craft()->userSession->getUser()->can('events-manageEventType:' . $eventTypeId)) {
            throw new HttpException(403, Craft::t('This action is not allowed for the current user.'));
        }
    }

    private function _prepareVariableArray(array &$variables)
    {
        // Locale related checks
        $variables['localeIds'] = craft()->i18n->getEditableLocaleIds();

        if (!$variables['localeIds']) {
            throw new HttpException(403, Craft::t('Your account doesn’t have permission to edit any of this site’s locales.'));
        }

        if (empty($variables['localeId'])) {
            $variables['localeId'] = craft()->language;

            if (!in_array($variables['localeId'], $variables['localeIds'], false)) {
                $variables['localeId'] = $variables['localeIds'][0];
            }
        } else {
            // Make sure they were requesting a valid locale
            if (!in_array($variables['localeId'], $variables['localeIds'], false)) {
                throw new HttpException(404);
            }
        }

        // Event related checks
        if (empty($variables['event'])) {
            if (!empty($variables['eventId'])) {
                $variables['event'] = EventsHelper::getEventsService()->getEventById($variables['eventId'], $variables['localeId']);

                if (!$variables['event']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['event'] = new Events_EventModel();
                $variables['event']->typeId = $variables['eventType']->id;

                if (!empty($variables['localeId'])) {
                    $variables['event']->locale = $variables['localeId'];
                }
            }
        }

        // Enable locales
        if (!empty($variables['event']->id)) {
            $variables['enabledLocales'] = craft()->elements->getEnabledLocalesForElement($variables['event']->id);
        } else {
            $variables['enabledLocales'] = [];

            foreach (craft()->i18n->getEditableLocaleIds() as $locale) {
                $variables['enabledLocales'][] = $locale;
            }
        }

        // Set up tabs
        $variables['tabs'] = [];

        foreach ($variables['eventType']->getFieldLayout()->getTabs() as $index => $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;
            if ($variables['event']->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    if ($variables['event']->getErrors($field->getField()->handle)) {
                        $hasErrors = true;
                        break;
                    }
                }
            }

            $variables['tabs'][] = [
                'label' => Craft::t($tab->name),
                'url'   => '#tab' . ($index + 1),
                'class' => $hasErrors ? 'error' : null,
            ];
        }

        // Set up title and the URL for continuing editing the event
        if (!empty($variables['event']->id)) {
            $variables['title'] = $variables['event']->title;
        } else {
            $variables['title'] = Craft::t('Create a new event');
        }

        $variables['continueEditingUrl'] = 'events/events/' . $variables['eventType']->handle . '/{id}' . (craft()->isLocalized() && !empty($variables['localeId']) && craft()->getLanguage() != $variables['localeId'] ? '/' . $variables['localeId'] : '');
    }

    private function _maybeEnableLivePreview(array &$variables)
    {
        if (!craft()->request->isMobileBrowser(true)
            && !empty($variables['eventType'])
            && EventsHelper::getEventTypesService()->isEventTypeTemplateValid($variables['eventType'])
        ) {
            craft()->templates->includeJs('Craft.LivePreview.init(' . JsonHelper::encode([
                    'fields'        => '#title-field, #fields > div > div > .field, #sku-field, #price-field',
                    'extraFields'   => '#meta-pane .field',
                    'previewUrl'    => $variables['event']->getUrl(),
                    'previewAction' => 'events/events/previewEvent',
                    'previewParams' => [
                        'typeId'  => $variables['eventType']->id,
                        'eventId' => $variables['event']->id,
                        'locale'  => $variables['event']->locale,
                    ],
                ]) . ');');

            $variables['showPreviewBtn'] = true;

            // Should we show the Share button too?
            if ($variables['event']->id) {
                // If the event is enabled, use its main URL as its share URL.
                if ($variables['event']->getStatus() == Events_EventModel::LIVE) {
                    $variables['shareUrl'] = $variables['event']->getUrl();
                } else {
                    $variables['shareUrl'] = UrlHelper::getActionUrl('events/events/shareEvent', [
                        'eventId' => $variables['event']->id,
                        'locale'  => $variables['event']->locale,
                    ]);
                }
            }
        } else {
            $variables['showPreviewBtn'] = false;
        }
    }

    private function _buildEventFromPost()
    {
        $eventId = craft()->request->getPost('eventId');
        $locale = craft()->request->getPost('locale');

        if ($eventId) {
            $event = EventsHelper::getEventsService()->getEventById($eventId, $locale);

            if (!$event) {
                throw new Exception(Craft::t('No event with the ID “{id}”', ['id' => $eventId]));
            }
        } else {
            $event = new Events_EventModel();
        }

        $data = craft()->request->getPost();

        if (isset($data['typeId'])) {
            $event->typeId = $data['typeId'];
        }

        if (isset($data['enabled'])) {
            $event->enabled = $data['enabled'];
        }

        $startDate = craft()->request->getPost('startDate');
        $endDate = craft()->request->getPost('endDate');

        $event->startDate = $startDate ? DateTime::createFromString($startDate, craft()->timezone) : $event->startDate;
        $event->endDate = $endDate ? DateTime::createFromString($endDate, craft()->timezone) : null;
        $event->allDay = craft()->request->getPost('allDay');

        if (!$event->startDate) {
            $event->startDate = new DateTime();
        }

        $event->capacity = craft()->request->getPost('capacity');

        $event->slug = $data['slug'] ? $data['slug'] : $event->slug;

        $event->localeEnabled = (bool)craft()->request->getPost('localeEnabled', $event->localeEnabled);
        $event->getContent()->title = craft()->request->getPost('title', $event->title);
        $event->setContentFromPost('fields');

        return $event;
    }


    /**
     * @param Events_TicketModel[] $tickets
     *
     * @return array
     * @throws Exception
     */
    private function _getTicketRowHtml(array $tickets = null)
    {
        $originalNamespace = craft()->templates->getNamespace();
        $namespace = craft()->templates->namespaceInputName('tickets[__ROWID__]', $originalNamespace);
        craft()->templates->setNamespace($namespace);
        craft()->templates->startJsBuffer();

//        if(count($tickets) > 0) {
//            $bodyHtml = '';
//
//            foreach ($tickets as $ticket) {
//                $bodyHtml.= craft()->templates->namespaceInputs(
//                    craft()->templates->render('events/_includes/ticketrow', array(
//                        'ticketId' => $ticket->id,
//                        'ticketTypes' => $ticket->getTicketTypes(),
//                        'ticketQuantity' => $ticket->quantity,
//                        'ticketPrice' => $ticket->getPrice()
//                    )
//                ));
//            }
//        } else {
        $bodyHtml = craft()->templates->namespaceInputs(
            craft()->templates->render('events/_includes/ticketrow', [
//                    'ticketId' => null,
                    'ticketTypes'         => null,
                    'ticketQuantity'      => null,
                    'ticketPrice'         => null,
                    'ticketAvailableFrom' => null,
                    'ticketAvailableTo'   => null,
                    'ticketType'          => null,
                ]
            ));
//        }

        $footHtml = craft()->templates->clearJsBuffer();

        craft()->templates->setNamespace($originalNamespace);

        return [
            'bodyHtml' => $bodyHtml,
            'footHtml' => $footHtml,
        ];
    }
}
