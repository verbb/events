<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\models\Settings;

use Craft;

use yii\web\Response;

use verbb\base\controllers\SettingsController as BaseSettingsController;

class SettingsController extends BaseSettingsController
{
    // Public Methods
    // =========================================================================

    public function actionIndex(): Response
    {
        /* @var Settings $settings */
        $settings = Events::$plugin->getSettings();

        return $this->renderTemplate('events/settings', [
            'settings' => $settings,
            'selectedTab' => Craft::$app->getRequest()->getSegment(3) ?: 'general',
            'eventStatusOptions' => $this->_eventStatusOptions(),
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _eventStatusOptions(): array
    {
        $options = [
            ['label' => Craft::t('events', 'All statuses'), 'value' => ''],
        ];

        foreach (Event::statuses() as $status => $label) {
            $options[] = ['label' => $label, 'value' => $status];
        }

        return $options;
    }
}
