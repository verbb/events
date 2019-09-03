<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\helpers\EventHelper;

use Craft;
use craft\base\Element;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\base\Exception;
use yii\web\HttpException;
use yii\web\Response;
use yii\web\ServerErrorHttpException;

class EventsPreviewController extends Controller
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionPreviewEvent(): Response
    {
        $this->requirePostRequest();

        $event = EventHelper::populateEventFromPost();

        $this->enforceEventPermissions($event);

        return $this->_showEvent($event);
    }

    public function actionShareEvent($eventId, $siteId): Response
    {
        $event = Events::getInstance()->getEvents()->getEventById($eventId, $siteId);

        if (!$event) {
            throw new HttpException(404);
        }

        $this->enforceEventPermissions($event);

        // Make sure the event actually can be viewed
        if (!Events::getInstance()->getEventTypes()->isEventTypeTemplateValid($event->getType(), $event->siteId)) {
            throw new HttpException(404);
        }

        // Create the token and redirect to the event URL with the token in place
        $token = Craft::$app->getTokens()->createToken([
            'events/events-preview/view-shared-event', ['eventId' => $event->id, 'siteId' => $siteId]
        ]);

        $url = UrlHelper::urlWithToken($event->getUrl(), $token);

        return $this->redirect($url);
    }

    public function actionViewSharedEvent($eventId, $site = null)
    {
        $this->requireToken();

        $event = Events::getInstance()->getEvents()->getEventById($eventId, $site);

        if (!$event) {
            throw new HttpException(404);
        }

        $this->_showEvent($event);

        return null;
    }

    public function actionSaveEvent()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $event = EventHelper::populateEventFromPost();

        $this->enforceEventPermissions($event);

        // Save the entry (finally!)
        if ($event->enabled && $event->enabledForSite) {
            $event->setScenario(Element::SCENARIO_LIVE);
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
                'event' => $event
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

        Craft::$app->getSession()->setNotice(Craft::t('events', 'Event saved.'));

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

    private function _showEvent(Event $event): Response
    {
        $eventType = $event->getType();

        if (!$eventType) {
            throw new ServerErrorHttpException('Event type not found.');
        }

        $siteSettings = $eventType->getSiteSettings();

        if (!isset($siteSettings[$event->siteId]) || !$siteSettings[$event->siteId]->hasUrls) {
            throw new ServerErrorHttpException('The event ' . $event->id . ' doesn\'t have a URL for the site ' . $event->siteId . '.');
        }

        $site = Craft::$app->getSites()->getSiteById($event->siteId);

        if (!$site) {
            throw new ServerErrorHttpException('Invalid site ID: ' . $event->siteId);
        }

        Craft::$app->language = $site->language;

        // Have this event override any freshly queried events with the same ID/site
        Craft::$app->getElements()->setPlaceholderElement($event);

        $this->getView()->getTwig()->disableStrictVariables();

        return $this->renderTemplate($siteSettings[$event->siteId]->template, [
            'event' => $event
        ]);
    }
}
