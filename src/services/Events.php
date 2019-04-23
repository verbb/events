<?php
namespace verbb\events\services;

use verbb\events\elements\Event;

use Craft;
use craft\db\Query;
use craft\events\SiteEvent;
use craft\helpers\App;
use craft\queue\jobs\ResaveElements;

use yii\base\Component;
use yii\base\Exception;

class Events extends Component
{
    // Public Methods
    // =========================================================================

    public function getEventById(int $id, $siteId = null)
    {
        return Craft::$app->getElements()->getElementById($id, Event::class, $siteId);
    }

    public function afterSaveSiteHandler(SiteEvent $event)
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
                ]
            ]));
        }
    }
}
