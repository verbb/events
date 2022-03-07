<?php
namespace verbb\events\services;

use verbb\events\elements\Event;

use Craft;
use craft\base\ElementInterface;
use craft\events\SiteEvent;
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
        $queue = Craft::$app->getQueue();
        $siteId = $event->oldPrimarySiteId;
        $elementTypes = [
            Event::class,
        ];

        foreach ($elementTypes as $elementType) {
            $queue->push(new ResaveElements([
                'elementType' => $elementType,
                'criteria' => [
                    'siteId' => $siteId,
                    'status' => null,
                    'enabledForSite' => false,
                ],
            ]));
        }
    }
}
