<?php

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use craft\commerce\events\ProcessPaymentEvent;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\records\InventoryItem as InventoryItemRecord;
use craft\commerce\services\Payments;
use craft\db\Query;
use craft\helpers\StringHelper;
use verbb\base\elements\db\CachedElementQuery;
use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\Session;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;
use verbb\events\helpers\TicketCapacity;
use yii\base\Event as YiiEvent;

$appPath = getenv('VERBB_TEST_APP');
$database = getenv('VERBB_TEST_DISPOSABLE_DATABASE');
if (!$appPath || !$database) {
    throw new RuntimeException('Set VERBB_TEST_APP and VERBB_TEST_DISPOSABLE_DATABASE to a disposable, populated Craft installation.');
}
$_SERVER['SCRIPT_FILENAME'] = $appPath . '/web/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
require $appPath . '/bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/web.php';
if ($app->db->createCommand('SELECT DATABASE()')->queryScalar() !== $database) {
    throw new RuntimeException('An explicitly named disposable database is required.');
}
$app->user->setIdentity(null);
$app->mailer->useFileTransport = true;
$checks = 0;
$check = static function(bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};
$reset = static function(): void {
    (new ReflectionProperty(CachedElementQuery::class, '_cache'))->setValue(null, []);
    (new ReflectionProperty(Ticket::find(), '_cache'))->setValue(null, []);
};
$save = static function($element) use ($app): void {
    if (!$app->elements->saveElement($element)) {
        throw new RuntimeException('Fixture save failed: ' . json_encode($element->getErrors()));
    }
};
$type = Events::$plugin->getEventTypes()->getAllEventTypes()[0];
$siteId = (int)array_key_first($type->getSiteSettings());
$app->sites->setCurrentSite($siteId);
$fixture = static function(?int $eventCapacity, ?int $sessionCapacity, ?int $ticketCapacity, int $seats = 1, int $sessionCount = 1, int $typeCount = 1) use ($save, $reset, $type, $siteId): array {
    $event = new Event(['typeId' => $type->id, 'siteId' => $siteId, 'title' => 'Capacity regression', 'capacity' => $eventCapacity, 'postDate' => new DateTime('-1 day')]);
    $save($event);
    $sessions = [];
    for ($i = 0; $i < $sessionCount; $i++) {
        $session = new Session(['primaryOwner' => $event, 'owner' => $event, 'siteId' => $siteId, 'capacity' => $sessionCapacity, 'startDate' => new DateTime('+6 months +' . $i . ' hours'), 'endDate' => new DateTime('+6 months +' . ($i + 1) . ' hours')]);
        $save($session);
        $sessions[] = $session;
    }
    for ($i = 0; $i < $typeCount; $i++) {
        $ticketType = new TicketType(['primaryOwner' => $event, 'owner' => $event, 'siteId' => $siteId, 'title' => 'Standard ' . $i, 'price' => 0, 'capacity' => $ticketCapacity, 'seatsPerTicket' => $seats]);
        $save($ticketType);
    }
    $reset();
    $event->updateTickets();
    $reset();
    return [$event, $sessions, Ticket::find()->eventId($event->id)->siteId($siteId)->orderBy(['id' => SORT_ASC])->all()];
};
$cart = static function(array $tickets, int $qty, array $options = []) use ($siteId, $save): Order {
    $order = new Order(['number' => StringHelper::randomString(32), 'orderSiteId' => $siteId, 'storeId' => Commerce::getInstance()->getStores()->getCurrentStore()->id, 'email' => 'capacity-' . StringHelper::randomString(10) . '@example.test']);
    $save($order);
    foreach ($tickets as $ticket) {
        $line = Commerce::getInstance()->getLineItems()->create($order, ['purchasableId' => $ticket->id, 'qty' => $qty, 'options' => $options]);
        $line->refresh();
        $order->addLineItem($line);
    }
    return $order;
};
$count = static fn(Order $order): int => (int)(new Query())->from('{{%events_purchased_tickets}}')->where(['orderId' => $order->id])->count();
$noticeCount = static fn(Order $order): int => (int)(new Query())->from('{{%commerce_ordernotices}}')->where(['orderId' => $order->id, 'type' => 'eventsTicketAllocation'])->count();

if (($argv[1] ?? null) === '--worker') {
    $order = Commerce::getInstance()->getOrders()->getOrderById((int)$argv[2]);
    $barrier = $argv[3];
    $transaction = $app->db->beginTransaction();
    // Establish a repeatable-read snapshot before the competing order writes.
    $app->db->createCommand('SELECT COUNT(*) FROM {{%events_purchased_tickets}}')->queryScalar();
    file_put_contents($barrier . '.' . $order->id, 'ready');
    $deadline = microtime(true) + 20;
    while (!is_file($barrier . '.go')) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Concurrency barrier timed out.');
        }
        usleep(20000);
    }
    try {
        $completed = $order->markAsComplete();
        usleep(200000);
        $transaction->commit();
        echo json_encode(['completed' => $completed, 'tickets' => $count($order), 'notices' => $noticeCount($order)]) . "\n";
    } finally {
        if ($transaction->isActive) {
            $transaction->rollBack();
        }
    }
    exit;
}

$transaction = $app->db->beginTransaction();
try {
    foreach ([
        'session' => [null, 10, 100, 1, 1, 2],
        'event' => [10, null, 100, 1, 2, 1],
    ] as $name => $configuration) {
        [$event, $sessions, $tickets] = $fixture(...$configuration);
        $order = $cart($tickets, 6);
        $order->validateLineItems();
        $check($order->hasErrors(), "$name: combined cart demand must be rejected.");
        $payment = new ProcessPaymentEvent(['order' => $order]);
        Commerce::getInstance()->getPayments()->trigger(Payments::EVENT_BEFORE_PROCESS_PAYMENT, $payment);
        $check(!$payment->isValid, "$name: payment must be blocked before a gateway is called.");
        $control = $cart($tickets, 4);
        $control->validateLineItems();
        $check(!$control->hasErrors(), "$name: a valid mixed cart must remain purchasable.");
        $save($control);
        $check($control->markAsComplete(), "$name: trusted PHP order completion must work.");
        $check($count($control) === 8, "$name: all ticket lines must be allocated once.");
        $check(!$control->hasAdminNotices(), "$name: a valid ticket order must not produce inventory admin notices.");
        $check(TicketCapacity::allocateOrder($control) && $count($control) === 8, "$name: repeated completion must be idempotent.");
        $check($tickets[0]->getStock() === 2, "$name: previously loaded ticket counts must refresh after completion.");
        $historical = Commerce::getInstance()->getOrders()->getOrderById($control->id);
        $historical->validateLineItems();
        $check(!$historical->hasErrors(), "$name: completed-order validation must preserve history.");
    }
    [$event, $sessions, $tickets] = $fixture(null, 9, 100, 4);
    $check($tickets[0]->getStock() === 2 && $tickets[0]->getRemainingSeats() === 9, 'Multi-seat stock must round down to whole tickets while seat counts remain available.');
    $order = $cart($tickets, 3);
    $order->validateLineItems();
    $check(!$order->hasErrors() && $order->getLineItems()[0]->qty === 2, 'Commerce must cap an oversized multi-seat line to purchasable ticket units.');
    $save($order);
    $check($order->markAsComplete() && $count($order) === 2, 'A multi-seat order must complete normally.');
    $check($sessions[0]->getRemainingSeatsCount() === 1 && $tickets[0]->getStock() === 0, 'Loaded seat counts must refresh and unusable remainder seats must not become another ticket.');
    [$displayEvent, $displaySessions, $displayTickets] = $fixture(null, null, 9, 4);
    $remaining = (new ReflectionMethod(Event::class, 'getAggregatedTicketsRemaining'))->invoke($displayEvent);
    $check($remaining === 9, 'The event’s aggregate Remaining Seats display must retain seat units.');

    [$event, $sessions, $tickets] = $fixture(null, null, null);
    $check($tickets[0]->getStock() === PHP_INT_MAX, 'Blank capacity must remain unlimited.');
    $unlimited = $cart($tickets, 2);
    $unlimited->validateLineItems();
    $check(!$unlimited->hasErrors(), 'Unlimited advance sales must remain available.');
    $save($unlimited);
    $check($unlimited->markAsComplete() && $count($unlimited) === 2, 'Unlimited tickets must complete and allocate normally.');
    $check(!$unlimited->hasAdminNotices(), 'Unlimited tickets must not produce inventory admin notices.');
    $check(!Commerce::getInstance()->getOrders()->getOrderById($unlimited->id)->hasAdminNotices(), 'An unlimited ticket order must reload without persisted inventory admin notices.');

    // Existing tickets can retain negative warehouse inventory from earlier purchases.
    $inventoryItem = new InventoryItemRecord([
        'purchasableId' => $tickets[0]->id,
        'countryCodeOfOrigin' => '',
        'administrativeAreaCodeOfOrigin' => '',
        'harmonizedSystemCode' => '',
    ]);
    $check($inventoryItem->save(), 'The legacy inventory fixture must save.');
    $inventory = Commerce::getInstance()->getInventory();
    $location = Commerce::getInstance()->getInventoryLocations()->getAllInventoryLocations()->first();
    $inventory->updateInventoryLevel($inventoryItem->id, -2, ['inventoryLocationId' => $location->id]);
    $reset();
    $legacyTicket = Ticket::find()->id($tickets[0]->id)->one();
    $check((int)$legacyTicket->inventoryItemId === (int)$inventoryItem->id, 'The existing ticket must load its legacy inventory record.');
    $legacy = $cart([$legacyTicket], 1);
    $save($legacy);
    $check($legacy->markAsComplete() && $count($legacy) === 1, 'Existing warehouse inventory must not prevent ticket allocation.');
    $check(!$legacy->hasAdminNotices(), 'Legacy negative inventory must not create a new ticket order notice.');
    $check($inventory->getInventoryLevel($inventoryItem->id, $location->id)->availableTotal === -2, 'Ticket completion must leave legacy warehouse inventory untouched.');

    [$zeroEvent, $zeroSessions, $zeroTickets] = $fixture(null, 0, null);
    $check($zeroTickets[0]->getStock() === 0 && !$zeroTickets[0]->getIsAvailable(), 'Zero capacity must remain unavailable.');

    // Capacity may change after a cart is accepted, including during an off-site payment.
    [$event, $sessions, $tickets] = $fixture(10, null, 100, 1, 1, 2);
    $late = $cart($tickets, 4);
    $save($late);
    $app->db->createCommand()->update('{{%events_events}}', ['capacity' => 5], ['id' => $event->id])->execute();
    $check($late->markAsComplete(), 'A late shortage must retain the completed Commerce order.');
    $check($count($late) === 0 && $noticeCount($late) === 1, 'A late shortage must issue no partial tickets and persist a fulfillment notice.');
    $check($late->isCompleted, 'Late allocation failure must not discard completed-order state.');

    // Any failed element save must roll back the entire ticket allocation.
    [$event, $sessions, $tickets] = $fixture(20, null, 100, 1, 1, 2);
    $failed = $cart($tickets, 2);
    $save($failed);
    $attempts = 0;
    $denySecond = static function($event) use (&$attempts): void {
        if (++$attempts === 2) {
            $event->isValid = false;
        }
    };
    YiiEvent::on(PurchasedTicket::class, PurchasedTicket::EVENT_BEFORE_SAVE, $denySecond);
    try {
        $check(!TicketCapacity::allocateOrder($failed), 'A failed ticket save must report allocation failure.');
        $check($count($failed) === 0 && $noticeCount($failed) === 1, 'A failed save must roll back prior ticket inserts and persist a notice.');
    } finally {
        YiiEvent::off(PurchasedTicket::class, PurchasedTicket::EVENT_BEFORE_SAVE, $denySecond);
    }
    $check(TicketCapacity::allocateOrder($failed) && $count($failed) === 4, 'A trusted PHP retry must succeed after a transient save failure.');

    [$event, $sessions, $tickets] = $fixture(1, 1, 1);
    $first = $cart($tickets, 1);
    $save($first);
    $check($first->markAsComplete(), 'The cancellation fixture must complete.');
    $reset();
    $purchase = PurchasedTicket::find()->orderId($first->id)->one();
    $service = Events::$plugin->getPurchasedTickets();
    $check($service->cancelPurchasedTicket($purchase), 'Cancellation must still release capacity.');
    $check($tickets[0]->getStock() === 1 && $sessions[0]->getRemainingSeatsCount() === 1, 'Loaded counts must refresh after cancellation.');
    $check(TicketCapacity::allocateOrder($first) && $count($first) === 1, 'Completion replay must not recreate cancelled reservations.');
    $second = $cart($tickets, 1);
    $save($second);
    $check($second->markAsComplete(), 'Released capacity must be available for a new order.');
    $check(!$service->canRestorePurchasedTicket($purchase) && !$service->restorePurchasedTicket($purchase), 'Restoration must not overbook capacity consumed by another order.');
    $reset();
    $other = PurchasedTicket::find()->orderId($second->id)->one();
    $check($service->cancelPurchasedTicket($other) && $service->restorePurchasedTicket($purchase), 'Restoration must work when capacity is available.');
    $check($sessions[0]->getRemainingSeatsCount() === 0, 'Restoration must update loaded seat counts.');
} finally {
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
}

// These dedicated fixtures must be committed so separate database connections can see them.
$orders = [];
$concurrentEvent = null;
$barrier = tempnam(sys_get_temp_dir(), 'events-capacity-');
unlink($barrier);
$processes = [];
try {
    [$concurrentEvent, $sessions, $tickets] = $fixture(1, 1, 1);
    foreach ([0, 1] as $index) {
        $order = $cart($tickets, 1);
        $save($order);
        $orders[] = $order;
        $process = proc_open([PHP_BINARY, __FILE__, '--worker', (string)$order->id, $barrier], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $check(is_resource($process), 'A concurrent worker must start.');
        fclose($pipes[0]);
        $processes[] = [$process, $pipes];
    }
    $deadline = microtime(true) + 20;
    while (count(glob($barrier . '.*')) !== 2) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Workers did not reach the concurrency barrier.');
        }
        usleep(20000);
    }
    file_put_contents($barrier . '.go', 'go');
    $results = [];
    foreach ($processes as [$process, $pipes]) {
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $check(proc_close($process) === 0, 'A concurrent worker failed: ' . $output . $error);
        $results[] = json_decode(trim($output), true, 512, JSON_THROW_ON_ERROR);
    }
    $processes = [];
    $check(array_sum(array_column($results, 'tickets')) === 1, 'Concurrent orders must not allocate the same last seat.');
    $check(array_sum(array_column($results, 'notices')) === 1, 'The competing order must retain a fulfillment notice.');
    $check(!in_array(false, array_column($results, 'completed'), true), 'Both completion records must be preserved.');
} finally {
    foreach ($processes as [$process, $pipes]) {
        if (is_resource($process)) {
            proc_terminate($process);
            proc_close($process);
        }
    }
    if ($concurrentEvent) {
        $reset();
        foreach (PurchasedTicket::find()->eventId($concurrentEvent->id)->status(null)->all() as $purchase) {
            $app->elements->deleteElement($purchase, true);
        }
    }
    foreach ($orders as $order) {
        $app->elements->deleteElement($order, true);
    }
    if ($concurrentEvent) {
        $app->elements->deleteElement($concurrentEvent, true);
    }
    foreach (glob($barrier . '.*') as $file) {
        unlink($file);
    }
}
echo "Passed $checks ticket capacity checks on PHP " . PHP_VERSION . ".\n";
