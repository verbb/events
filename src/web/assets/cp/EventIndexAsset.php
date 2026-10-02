<?php
namespace verbb\events\web\assets\cp;

use verbb\events\Events;
use verbb\events\models\EventType;

use Craft;
use craft\helpers\Json;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;

use verbb\base\web\assets\cp\CpAsset as VerbbCpAsset;

class EventIndexAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->sourcePath = '@verbb/events/web/assets/cp/dist';

        $this->depends = [
            VerbbCpAsset::class,
            CpAsset::class,
        ];

        $this->js = [
            'event-index.js',
        ];

        parent::init();
    }

    public function registerAssetFiles($view): void
    {
        parent::registerAssetFiles($view);

        // Define the Craft.Events object
        $eventsJson = Json::encode($this->_eventsData());

        $js = <<<JS
            window.Craft.Events = $eventsJson;
        JS;

        $view->registerJs($js, View::POS_HEAD);
    }

    private function _eventsData(): array
    {
        return [
            'defaultEventIndexStatus' => Events::$plugin->getSettings()->defaultEventIndexStatus,
            'editableEventTypes' => array_map(fn(EventType $eventType) => [
                'id' => $eventType->id,
                'uid' => $eventType->uid,
                'name' => Craft::t('site', $eventType->name),
                'handle' => $eventType->handle,
            ], Events::$plugin->getEventTypes()->getCreatableEventTypes()),
        ];
    }
}
