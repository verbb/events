<?php
namespace verbb\events\services;

use verbb\events\elements\Event;

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
        return Craft::$app->getElements()->getElementById($id, Event::class, $siteId);
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
