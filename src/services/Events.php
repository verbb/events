<?php
namespace verbb\events\services;

use verbb\events\Events as EventsPlugin;
use verbb\events\elements\Event;
use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\Ticket;

use Craft;
use craft\events\SiteEvent;
use craft\helpers\Queue;
use craft\queue\jobs\PropagateElements;

use yii\base\Component;

class Events extends Component
{
    // Public Methods
    // =========================================================================

    public function getEventById(int $id, int $siteId = null): ?Event
    {
        return Event::find()->id($id)->site($siteId)->status(null)->one();
    }

    /**
     * The first-run checklist on the plugin's landing page, ticked from real data. It shows until the first ticket is
     * purchased, counting trashed purchased tickets so deleting a test purchase doesn't bring it back.
     */
    public function getSetupSteps(): ?array
    {
        if (PurchasedTicket::find()->site('*')->status(null)->trashed(null)->exists()) {
            return null;
        }

        $firstEvent = Event::find()->site('*')->unique()->status(null)->orderBy(['elements.dateCreated' => SORT_ASC])->one();

        return [
            'eventType' => (bool)EventsPlugin::$plugin->getEventTypes()->getAllEventTypes(),
            'event' => $firstEvent !== null,
            // Events makes a ticket for each session and ticket type once the event saves, so a ticket means one can be sold.
            'tickets' => Ticket::find()->site('*')->status(null)->exists(),
            'firstEvent' => $firstEvent,
        ];
    }

    public function afterSaveSiteHandler(SiteEvent $event): void
    {
        if ($event->isNew && isset($event->oldPrimarySiteId)) {
            Queue::push(new PropagateElements([
                'elementType' => Event::class,
                'criteria' => [
                    'siteId' => $event->oldPrimarySiteId,
                    'status' => null,
                ],
                'siteId' => $event->site->id,
            ]));
        }
    }
}
