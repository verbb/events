<?php
namespace verbb\events\events;

use craft\commerce\elements\Order;

use yii\base\Event;

class PdfEvent extends Event
{
    // Properties
    // =========================================================================

    public ?Order $order = null;
    public ?string $option = null;
    public ?string $template = null;
    public array $variables = [];
    public mixed $pdf = null;
}
