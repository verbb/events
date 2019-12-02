<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\models\EventType;
use verbb\events\models\EventTypeSite;

use Craft;
use craft\web\Controller;

use yii\base\Exception;
use yii\web\Response;

class EventTypesController extends Controller
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->requirePermission('events-manageEventTypes');

        parent::init();
    }

    public function actionEdit(int $eventTypeId = null, EventType $eventType = null): Response
    {
        $variables = [
            'eventTypeId' => $eventTypeId,
            'eventType' => $eventType,
            'brandNewEventType' => false,
        ];

        if (empty($variables['eventType'])) {
            if (!empty($variables['eventTypeId'])) {
                $eventTypeId = $variables['eventTypeId'];
                $variables['eventType'] = Events::getInstance()->getEventTypes()->getEventTypeById($eventTypeId);

                if (!$variables['eventType']) {
                    throw new HttpException(404);
                }
            } else {
                $variables['eventType'] = new EventType();
                $variables['brandNewEventType'] = true;
            }
        }

        if (!empty($variables['eventTypeId'])) {
            $variables['title'] = $variables['eventType']->name;
        } else {
            $variables['title'] = Craft::t('events', 'Create a Event Type');
        }
        
        return $this->renderTemplate('events/event-types/_edit', $variables);
    }

    public function actionSave()
    {
        $this->requirePostRequest();

        $eventType = new EventType();

        $request = Craft::$app->getRequest();
        
        $eventType->id = $request->getBodyParam('eventTypeId');
        $eventType->name = $request->getBodyParam('name');
        $eventType->handle = $request->getBodyParam('handle');
        $eventType->hasTitleField = (bool)$request->getBodyParam('hasTitleField', $eventType->hasTitleField);
        $eventType->titleLabel = $request->getBodyParam('titleLabel', $eventType->titleLabel);
        $eventType->titleFormat = $request->getBodyParam('titleFormat', $eventType->titleFormat);
        $eventType->hasTickets = (bool)$request->getBodyParam('hasTickets', $eventType->hasTickets);

        // Site-specific settings
        $allSiteSettings = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $postedSettings = $request->getBodyParam('sites.' . $site->handle);

            $siteSettings = new EventTypeSite();
            $siteSettings->siteId = $site->id;
            $siteSettings->hasUrls = !empty($postedSettings['uriFormat']);

            if ($siteSettings->hasUrls) {
                $siteSettings->uriFormat = $postedSettings['uriFormat'];
                $siteSettings->template = $postedSettings['template'];
            } else {
                $siteSettings->uriFormat = null;
                $siteSettings->template = null;
            }

            $allSiteSettings[$site->id] = $siteSettings;
        }

        $eventType->setSiteSettings($allSiteSettings);

        // Set the event type field layout
        $fieldLayout = Craft::$app->getFields()->assembleLayoutFromPost();
        $fieldLayout->type = Event::class;
        $eventType->setFieldLayout($fieldLayout);

        // Save it
        if (Events::getInstance()->getEventTypes()->saveEventType($eventType)) {
            Craft::$app->getSession()->setNotice(Craft::t('events', 'Event type saved.'));

            return $this->redirectToPostedUrl($eventType);
        }

        Craft::$app->getSession()->setError(Craft::t('events', 'Couldn’t save event type.'));

        // Send the eventType back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'eventType' => $eventType
        ]);

        return null;
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $eventTypeId = Craft::$app->getRequest()->getRequiredParam('id');
        Events::getInstance()->getEventTypes()->deleteEventTypeById($eventTypeId);

        return $this->asJson(['success' => true]);
    }

}
