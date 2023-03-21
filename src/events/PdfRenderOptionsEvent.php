<?php
namespace verbb\events\events;

use Dompdf\Options;

use yii\base\Event;

class PdfRenderOptionsEvent extends Event
{
    public array|Options $options = [];
}
