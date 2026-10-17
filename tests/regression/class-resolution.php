<?php

$vendor = getenv('VERBB_TEST_VENDOR') ?: dirname(__DIR__, 2) . '/vendor';
require $vendor . '/autoload.php';
require_once $vendor . '/yiisoft/yii2/Yii.php';
require_once $vendor . '/craftcms/cms/src/Craft.php';
$checks = 0;
$check = static function(bool $value, string $message) use (&$checks): void {
    if (!$value) {
        throw new RuntimeException($message);
    }
    $checks++;
};

$key = 'events-craft4';
$ticket = new \verbb\events\records\Ticket();
$event = $ticket->getEvent();
$type = $ticket->getType();
$check($event->modelClass === \verbb\events\records\Event::class && $event->link === ['id' => 'eventId'], 'Ticket event relations must target the Event record.');
$check($type->modelClass === \verbb\events\records\TicketType::class && $type->link === ['id' => 'typeId'], 'Ticket type relations must target the TicketType record.');
if (str_ends_with($key, 'craft5')) {
    $relation = (new \verbb\events\records\TicketType())->getEvent();
    $check($relation->modelClass === \verbb\events\records\Event::class, 'Ticket type event relations must target Event.');
}

echo "Passed $checks behavioral regression checks on PHP " . PHP_VERSION . ".\n";
