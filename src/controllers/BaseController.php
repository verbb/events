<?php
namespace verbb\events\controllers;

use verbb\events\Events;

use Craft;
use craft\web\Controller;

class BaseController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionSettings()
    {
        $settings = Events::$plugin->getSettings();

        return $this->renderTemplate('events/settings', [
            'settings' => $settings,
        ]);
    }

}