<?php
namespace verbb\events\events;

use Dompdf\Options;

use yii\base\Event;

class PdfRenderOptionsEvent extends Event
{
    // Properties
    // =========================================================================

    public array|Options $options = [];
}
