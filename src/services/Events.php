<?php
namespace verbb\events\services;

use verbb\events\Events as EventsPlugin;
use verbb\events\elements\Event;

use Craft;
use craft\events\SiteEvent;
use craft\helpers\Queue;
use craft\queue\jobs\PropagateElements;

use yii\base\Component;

use DateTime;
use DateTimeZone;

class Events extends Component
{
    // Constants
    // =========================================================================

    // Installs made before this date are existing sites, which never see the setup checklist.
    public const SETUP_CHECKLIST_SINCE = '2026-11-23';


    // Public Methods
    // =========================================================================

    public function getEventById(int $id, int $siteId = null): ?Event
    {
        return Event::find()->id($id)->site($siteId)->status(null)->one();
    }

    /**
     * The first-run checklist on the plugin's landing page, ticked from real data. It shows only on installs made since
     * the checklist was added, so updated sites never see it, and leaves at the first event, counting trashed ones so
     * deleting a test event doesn't bring it back.
     */
    public function getSetupSteps(): ?array
    {
        $installDate = Craft::$app->getPlugins()->getStoredPluginInfo('events')['installDate'] ?? null;

        // Craft stores the install date in UTC. A missing date is treated as an existing install.
        if (!$installDate instanceof DateTime || $installDate < new DateTime(self::SETUP_CHECKLIST_SINCE, new DateTimeZone('UTC'))) {
            return null;
        }

        if (Event::find()->site('*')->status(null)->trashed(null)->exists()) {
            return null;
        }

        return [
            'eventType' => (bool)EventsPlugin::$plugin->getEventTypes()->getAllEventTypes(),
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
