<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\assetbundles\EventIndexAsset;
use verbb\events\elements\Event;

use Craft;
use craft\base\Element;
use craft\helpers\Cp;
use craft\helpers\DateTimeHelper;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class EventsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionIndex(?string $eventTypeHandle = null): Response
    {
        $this->getView()->registerAssetBundle(EventIndexAsset::class);

        return $this->renderTemplate('events/events', [
            'eventTypeHandle' => $eventTypeHandle,
        ]);
    }

    public function actionCreate(?string $eventType = null): ?Response
    {
        if ($eventType) {
            $eventTypeHandle = $eventType;
        } else {
            $eventTypeHandle = $this->request->getRequiredBodyParam('eventType');
        }

        $eventType = Events::getInstance()->getEventTypes()->getEventTypeByHandle($eventTypeHandle);

        if (!$eventType) {
            throw new BadRequestHttpException("Invalid event type handle: $eventTypeHandle");
        }

        $sitesService = Craft::$app->getSites();
        $siteId = $this->request->getBodyParam('siteId');

        if ($siteId) {
            $site = $sitesService->getSiteById($siteId);

            if (!$site) {
                throw new BadRequestHttpException("Invalid site ID: $siteId");
            }
        } else {
            $site = Cp::requestedSite();

            if (!$site) {
                throw new ForbiddenHttpException('User not authorized to edit content in any sites.');
            }
        }

        $editableSiteIds = $sitesService->getEditableSiteIds();

        if (!in_array($site->id, $editableSiteIds)) {
            // Go with the first one
            $site = $sitesService->getSiteById($editableSiteIds[0]);
        }

        $user = static::currentUser();

        // Create & populate the draft
        $event = Craft::createObject(Event::class);
        $event->siteId = $site->id;
        $event->typeId = $eventType->id;
        $event->enabled = true;

        // Make sure the user is allowed to create this entry
        if (!Craft::$app->getElements()->canSave($event, $user)) {
            throw new ForbiddenHttpException('User not authorized to create this event.');
        }

        // Title & slug
        $event->title = $this->request->getParam('title');
        $event->slug = $this->request->getParam('slug');

        if ($event->title && !$event->slug) {
            $event->slug = ElementHelper::generateSlug($event->title, null, $site->language);
        }

        if (!$event->slug) {
            $event->slug = ElementHelper::tempSlug();
        }

        // Pause time so postDate will definitely be equal to dateCreated, if not explicitly defined
        DateTimeHelper::pause();

        // Post & expiry dates
        if (($postDate = $this->request->getParam('postDate')) !== null) {
            $event->postDate = DateTimeHelper::toDateTime($postDate);
        } else {
            $event->postDate = DateTimeHelper::now();
        }

        if (($expiryDate = $this->request->getParam('expiryDate')) !== null) {
            $event->expiryDate = DateTimeHelper::toDateTime($expiryDate);
        }

        // Custom fields
        foreach ($event->getFieldLayout()->getCustomFields() as $field) {
            if (($value = $this->request->getParam($field->handle)) !== null) {
                $event->setFieldValue($field->handle, $value);
            }
        }

        // Save it
        $event->setScenario(Element::SCENARIO_ESSENTIALS);
        $success = Craft::$app->getDrafts()->saveElementAsDraft($event, $user->id, markAsSaved: false);

        // Resume time
        DateTimeHelper::resume();

        if (!$success) {
            return $this->asModelFailure($event, Craft::t('app', 'Couldn’t create {type}.', [
                'type' => Event::lowerDisplayName(),
            ]), 'event');
        }

        $editUrl = $event->getCpEditUrl();

        $response = $this->asModelSuccess($event, Craft::t('app', '{type} created.', [
            'type' => Event::displayName(),
        ]), 'event', array_filter([
            'cpEditUrl' => $this->request->getIsCpRequest() ? $editUrl : null,
        ]));

        if (!$this->request->getAcceptsJson()) {
            $response->redirect(UrlHelper::urlWithParams($editUrl, [
                'fresh' => 1,
            ]));
        }

        return $response;
    }
}
