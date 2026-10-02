<?php
namespace verbb\events\web\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

use verbb\base\web\assets\cp\CpAsset as VerbbCpAsset;

class SessionIndexAsset extends AssetBundle
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
            'session-index.js',
        ];

        $this->css = [
            'session-index.css',
        ];

        parent::init();
    }
}
