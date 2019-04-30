<?php
namespace verbb\events\assetbundles;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class EventsAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    public function init()
    {
        $this->sourcePath = "@verbb/events/resources/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'css/events.css',
        ];

        $this->js = [
            'js/events.js',
        ];

        parent::init();
    }
}
