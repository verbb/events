<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\models\EventType;
use verbb\events\models\EventTypeSite;

use Craft;
use craft\web\Controller;

use yii\web\Response;
use yii\web\NotFoundHttpException;

use DateTime;
use DateTimeZone;

class EventTypesController extends Controller
{
    // Public Methods
    // =========================================================================

    public function init(): void
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
                $variables['eventType'] = Events::$plugin->getEventTypes()->getEventTypeById($eventTypeId);

                if (!$variables['eventType']) {
                    throw new NotFoundHttpException();
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

        $variables['timezoneOptions'] = $this->_getTimezoneOptions();

        return $this->renderTemplate('events/event-types/_edit', $variables);
    }

    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $eventType = new EventType();

        $eventType->id = $this->request->getBodyParam('eventTypeId');
        $eventType->name = $this->request->getBodyParam('name');
        $eventType->handle = $this->request->getBodyParam('handle');
        $eventType->hasTitleField = (bool)$this->request->getBodyParam('hasTitleField', $eventType->hasTitleField);
        $eventType->titleLabel = $this->request->getBodyParam('titleLabel', $eventType->titleLabel);
        $eventType->titleFormat = $this->request->getBodyParam('titleFormat', $eventType->titleFormat);
        $eventType->hasTickets = (bool)$this->request->getBodyParam('hasTickets', $eventType->hasTickets);
        $eventType->icsTimezone = $this->request->getBodyParam('icsTimezone', $eventType->icsTimezone);
        $eventType->icsDescriptionFieldHandle = $this->request->getBodyParam('icsDescriptionFieldHandle', $eventType->icsDescriptionFieldHandle);
        $eventType->icsLocationFieldHandle = $this->request->getBodyParam('icsLocationFieldHandle', $eventType->icsLocationFieldHandle);

        // Site-specific settings
        $allSiteSettings = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $postedSettings = $this->request->getBodyParam('sites.' . $site->handle);

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
        if (Events::$plugin->getEventTypes()->saveEventType($eventType)) {
            Craft::$app->getSession()->setNotice(Craft::t('events', 'Event type saved.'));

            return $this->redirectToPostedUrl($eventType);
        }

        Craft::$app->getSession()->setError(Craft::t('events', 'Couldn’t save event type.'));

        // Send the eventType back to the template
        Craft::$app->getUrlManager()->setRouteParams([
            'eventType' => $eventType,
        ]);

        return null;
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $eventTypeId = Craft::$app->getRequest()->getRequiredParam('id');
        Events::$plugin->getEventTypes()->deleteEventTypeById($eventTypeId);

        return $this->asJson(['success' => true]);
    }


    // Private Methods
    // =========================================================================

    private function _getTimezoneOptions(): array
    {
        // Assemble the timezone options array (Technique adapted from http://stackoverflow.com/a/7022536/1688568)
        $timezoneOptions = [];

        $utc = new DateTime();
        $offsets = [];
        $timezoneIds = [];

        foreach (DateTimeZone::listIdentifiers() as $timezoneId) {
            $timezone = new DateTimeZone($timezoneId);
            $transition = $timezone->getTransitions($utc->getTimestamp(), $utc->getTimestamp());
            $abbr = $transition[0]['abbr'];

            $offset = round($timezone->getOffset($utc) / 60);

            if ($offset) {
                $hour = floor($offset / 60);
                $minutes = floor(abs($offset) % 60);

                $format = sprintf('%+d', $hour);

                if ($minutes) {
                    $format .= ':' . sprintf('%02u', $minutes);
                }
            } else {
                $format = '';
            }

            $offsets[] = $offset;
            $timezoneIds[] = $timezoneId;
            $timezoneOptions[] = [
                'value' => $timezoneId,
                'label' => 'UTC' . $format . ($abbr !== 'UTC' ? " ({$abbr})" : '') . ($timezoneId !== 'UTC' ? ' – ' . $timezoneId : ''),
            ];
        }

        array_multisort($offsets, $timezoneIds, $timezoneOptions);

        $appended[] = [
            'value' => '',
            'label' => Craft::t('events', 'Floating Timezone (recommended)'),
        ];

        return array_merge($appended, $timezoneOptions);
    }

}
