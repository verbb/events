<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\models\Settings;

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
        ]);
    }

}