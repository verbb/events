<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\elements\Ticket;
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

class EventsController extends Controller
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = ['view-shared-event'];


    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->requirePermission('events-manageEvents');

        parent::init();
    }

    public function actionIndex(): Response
    {
        return $this->renderTemplate('events/events/index');
    }

    public function actionEdit(string $eventTypeHandle, int $eventId = null, string $siteHandle = null, Event $event = null): Response
    {
        $variables = [
            'eventTypeHandle' => $eventTypeHandle,
            'eventId' => $eventId,
            'event' => $event,
        ];

        if ($siteHandle !== null) {
            $variables['site'] = Craft::$app->getSites()->getSiteByHandle($siteHandle);

            if (!$variables['site']) {
                throw new NotFoundHttpException('Invalid site handle: ' . $siteHandle);
            }
        }

        if ($event && $event->getTickets()) {
            $variables['tickets'] = $event->getTickets();
        }

        $this->_prepEditEventVariables($variables);

        if (!empty($variables['event']->id)) {
            $variables['title'] = $variables['event']->title;
        } else {
            $variables['title'] = Craft::t('events', 'Create a new event');
        }

        // Can't just use the entry's getCpEditUrl() because that might include the site handle when we don't want it
        $variables['baseCpEditUrl'] = 'events/events/' . $variables['eventTypeHandle'] . '/{id}-{slug}';

        // Set the "Continue Editing" URL
        $variables['continueEditingUrl'] = $variables['baseCpEditUrl'] . (Craft::$app->getIsMultiSite() && Craft::$app->getSites()->currentSite->id !== $variables['site']->id ? '/' . $variables['site']->handle : '');

        $this->_prepVariables($variables);

        // Enable Live Preview?
        if (!Craft::$app->getRequest()->isMobileBrowser(true) && Events::getInstance()->getEventTypes()->isEventTypeTemplateValid($variables['eventType'], $variables['site']->id)) {
            $this->getView()->registerJs('Craft.LivePreview.init(' . Json::encode([
                'fields' => '#title-field, #fields > div > div > .field',
                'extraFields' => '#details',
                'previewUrl' => $variables['event']->getUrl(),
                'previewAction' => Craft::$app->getSecurity()->hashData('events/events-preview/preview-event'),
                'previewParams' => [
                    'typeId' => $variables['eventType']->id,
                    'eventId' => $variables['event']->id,
                    'siteId' => $variables['event']->siteId,
                ]
            ]) . ');');

            $variables['showPreviewBtn'] = true;

            // Should we show the Share button too?
            if ($variables['event']->id) {
                // If the event is enabled, use its main URL as its share URL.
                if ($variables['event']->getStatus() == Event::STATUS_LIVE) {
                    $variables['shareUrl'] = $variables['event']->getUrl();
                } else {
                    $variables['shareUrl'] = UrlHelper::actionUrl('events/events-preview/share-event', [
                        'eventId' => $variables['event']->id,
                        'siteId' => $variables['event']->siteId
                    ]);
                }
            }
        } else {
            $variables['showPreviewBtn'] = false;
        }

        return $this->renderTemplate('events/events/_edit', $variables);
    }

    public function actionDelete()
    {
        $this->requirePostRequest();

        $eventId = Craft::$app->getRequest()->getRequiredParam('eventId');
        $event = Events::getInstance()->getEvents()->getEventById($eventId);

        if (!$event) {
            throw new Exception(Craft::t('events', 'No event exists with the ID “{id}”.',['id' => $eventId]));
        }

        $this->enforceEventPermissions($event);

        if (!Craft::$app->getElements()->deleteElement($event)) {
            if (Craft::$app->getRequest()->getAcceptsJson()) {
                $this->asJson(['success' => false]);
            }

            Craft::$app->getSession()->setError(Craft::t('events', 'Couldn’t delete event.'));
            Craft::$app->getUrlManager()->setRouteParams([
                'event' => $event
            ]);

            return null;
        }

        if (Craft::$app->getRequest()->getAcceptsJson()) {
            $this->asJson(['success' => true]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('events', 'Event deleted.'));

        return $this->redirectToPostedUrl($event);
    }

    public function actionSave()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $event = EventHelper::populateEventFromPost();

        $this->enforceEventPermissions($event);

        if ($event->enabled && $event->enabledForSite) {
            $event->setScenario(Element::SCENARIO_LIVE);

            foreach ($event->getTickets() as $ticket) {
                $ticket->setScenario(Element::SCENARIO_LIVE);
            }
        }

        if (!Craft::$app->getElements()->saveElement($event)) {
            if ($request->getAcceptsJson()) {
                return $this->asJson([
                    'success' => false,
                    'errors' => $event->getErrors(),
                ]);
            }

            Craft::$app->getSession()->setError(Craft::t('events', 'Couldn’t save event.'));

            // Send the category back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'event' => $event,
            ]);

            return null;
        }

        if ($request->getAcceptsJson()) {
            return $this->asJson([
                'success' => true,
                'id' => $event->id,
                'title' => $event->title,
                'status' => $event->getStatus(),
                'url' => $event->getUrl(),
                'cpEditUrl' => $event->getCpEditUrl()
            ]);
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Event saved.'));

        return $this->redirectToPostedUrl($event);
    }


    // Protected Methods
    // =========================================================================

    protected function enforceEventPermissions(Event $event)
    {
        $this->requirePermission('events-manageEventType:' . $event->getType()->uid);
    }


    // Private Methods
    // =========================================================================

    private function _prepVariables(&$variables)
    {
        $variables['tabs'] = [];

        $eventType = $variables['eventType'];
        $event = $variables['event'];

        foreach ($eventType->getEventFieldLayout()->getTabs() as $index => $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;
            
            if ($event->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    if ($hasErrors = $event->hasErrors($field->handle . '.*')) {
                        break;
                    }
                }
            }

            $variables['tabs'][] = [
                'label' => Craft::t('events', $tab->name),
                'url' => '#' . $tab->getHtmlId(),
                'class' => $hasErrors ? 'error' : null
            ];
        }

        $variables['tabs'][] = [
            'label' => Craft::t('events', 'Dates/Times'),
            'url' => '#tab-dates-container',
        ];

        $hasErrors = false;
        
        foreach ($event->getTickets() as $ticket) {
            if ($hasErrors = $ticket->hasErrors()) {
                break;
            }
        }

        if ($event->getErrors('tickets')) {
            $hasErrors = true;
        }

        if ($eventType->hasTickets) {
            $variables['tabs'][] = [
                'label' => Craft::t('events', 'Tickets'),
                'url' => '#tab-tickets-container',
                'class' => $hasErrors ? 'error' : null
            ];
        }

        $variables['ticketRowHtml'] = TicketHelper::getTicketRowHtml();

        // Store each ticket type's field content so that it can be applied when selecting one
        $variables['ticketTypeHtml'] = $this->_getTicketTypeHtml();
    }

    private function _prepEditEventVariables(array &$variables)
    {
        if (!empty($variables['eventTypeHandle'])) {
            $variables['eventType'] = Events::getInstance()->getEventTypes()->getEventTypeByHandle($variables['eventTypeHandle']);
        } else if (!empty($variables['eventTypeId'])) {
            $variables['eventType'] = Events::getInstance()->getEventTypes()->getEventTypeById($variables['eventTypeId']);
        }

        if (empty($variables['eventType'])) {
            throw new NotFoundHttpException('Event Type not found');
        }

        // Get the site
        // ---------------------------------------------------------------------

        if (Craft::$app->getIsMultiSite()) {
            // Only use the sites that the user has access to
            $variables['siteIds'] = Craft::$app->getSites()->getEditableSiteIds();
        } else {
            $variables['siteIds'] = [Craft::$app->getSites()->getPrimarySite()->id];
        }

        if (!$variables['siteIds']) {
            throw new ForbiddenHttpException('User not permitted to edit content in any sites supported by this event type');
        }

        if (empty($variables['site'])) {
            $variables['site'] = Craft::$app->getSites()->currentSite;

            if (!in_array($variables['site']->id, $variables['siteIds'], false)) {
                $variables['site'] = Craft::$app->getSites()->getSiteById($variables['siteIds'][0]);
            }

            $site = $variables['site'];
        } else {
            // Make sure they were requesting a valid site
            $site = $variables['site'];
            if (!in_array($site->id, $variables['siteIds'], false)) {
                throw new ForbiddenHttpException('User not permitted to edit content in this site');
            }
        }

        if (!empty($variables['eventTypeHandle'])) {
            $variables['eventType'] = Events::getInstance()->getEventTypes()->getEventTypeByHandle($variables['eventTypeHandle']);
        }

        if (empty($variables['eventType'])) {
            throw new HttpException(400, craft::t('events', 'Wrong event type specified'));
        }

        // Get the event
        // ---------------------------------------------------------------------

        if (empty($variables['event'])) {
            if (!empty($variables['eventId'])) {
                $variables['event'] = Events::getInstance()->getEvents()->getEventById($variables['eventId'], $variables['site']->id);

                if (!$variables['event']) {
                    throw new NotFoundHttpException('Event not found');
                }
            } else {
                $variables['event'] = new Event();
                $variables['event']->typeId = $variables['eventType']->id;
                $variables['event']->enabled = true;
                $variables['event']->siteId = $site->id;
            }
        }

        if ($variables['event']->id) {
            $this->enforceEventPermissions($variables['event']);
            $variables['enabledSiteIds'] = Craft::$app->getElements()->getEnabledSiteIdsForElement($variables['event']->id);
        } else {
            $variables['enabledSiteIds'] = [];

            foreach (Craft::$app->getSites()->getEditableSiteIds() as $site) {
                $variables['enabledSiteIds'][] = $site;
            }
        }

        // Get the tickets
        // ---------------------------------------------------------------------

        if (empty($variables['tickets'])) {
            if ($variables['event']->id) {
                $variables['tickets'] = Events::getInstance()->getTickets()->getAllTicketsByEventId($variables['event']->id);
            } else {
                // Always have at least one row for new events
                $variables['tickets'] = [];
            }
        }
    }

    private function _getTicketTypeHtml()
    {
        $ticketTypes = Events::$plugin->getTicketTypes()->getAllTicketTypes();
        $html = [];

        $originalNamespace = Craft::$app->getView()->getNamespace();
        $namespace = Craft::$app->getView()->namespaceInputName('tickets[__ROWID__]', $originalNamespace);
        Craft::$app->getView()->setNamespace($namespace);

        foreach ($ticketTypes as $ticketType) {
            Craft::$app->getView()->startJsBuffer();

            $bodyHtml = Craft::$app->getView()->namespaceInputs(Craft::$app->getView()->renderTemplate('events/_includes/ticket-type-fields', [
                'namespace' => null,
                'ticketType' => $ticketType,
            ]));

            $footHtml = Craft::$app->getView()->clearJsBuffer();

            $html[$ticketType->id] = [
                'bodyHtml' => $bodyHtml,
                'footHtml' => $footHtml,
            ];
        }

        Craft::$app->getView()->setNamespace($originalNamespace);

        return $html;
    }
}
