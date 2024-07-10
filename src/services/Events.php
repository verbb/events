<?php
namespace verbb\events\services;

use verbb\events\elements\Event;

use Craft;
use craft\base\ElementInterface;
use craft\events\SiteEvent;
use craft\helpers\Queue;
use craft\queue\jobs\ResaveElements;

use yii\base\Component;

class Events extends Component
{
    // Public Methods
    // =========================================================================

    public function getEventById(int $id, $siteId = null): ?Event
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($id, Event::class, $siteId);
    }

    public function afterSaveSiteHandler(SiteEvent $event): void
    {
        if ($event->isNew && isset($event->oldPrimarySiteId)) {
            $oldPrimarySiteId = $event->oldPrimarySiteId;
            
            $elementTypes = [
                Event::class,
            ];

            foreach ($elementTypes as $elementType) {
                Queue::push(new ResaveElements([
                    'elementType' => $elementType,
                    'criteria' => [
                        'siteId' => $oldPrimarySiteId,
                        'status' => null,
                    ],
                ]));
            }
        }
    }
}
