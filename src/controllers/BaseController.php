<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\models\Settings;

use Craft;
use craft\web\Controller;

use yii\web\Response;

class BaseController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSettings(): Response
    {
        /* @var Settings $settings */
        $settings = Events::$plugin->getSettings();

        return $this->renderTemplate('events/settings', [
            'settings' => $settings,
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