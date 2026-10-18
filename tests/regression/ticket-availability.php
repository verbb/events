<?php

use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use craft\helpers\StringHelper;
use verbb\base\elements\db\CachedElementQuery;
use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\elements\Session;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;

$appPath = getenv('VERBB_TEST_APP');
$database = getenv('VERBB_TEST_DISPOSABLE_DATABASE');
$userId = getenv('VERBB_TEST_CP_USER_ID');

if (!$appPath || !$database || !$userId) {
    throw new RuntimeException('Set VERBB_TEST_APP, VERBB_TEST_DISPOSABLE_DATABASE and VERBB_TEST_CP_USER_ID to a disposable, populated Craft installation.');
}

$_SERVER['SCRIPT_FILENAME'] = $appPath . '/web/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_ACCEPT'] = 'application/json';

require $appPath . '/bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/web.php';

$checks = 0;
$check = static function(bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};
$check($app->db->createCommand('SELECT DATABASE()')->queryScalar() === $database, 'An explicitly named disposable database is required.');
$save = static function($element) use ($app): void {
    if (!$app->elements->saveElement($element)) {
        throw new RuntimeException(json_encode($element->getErrors()));
    }
};
$resetCache = static function(): void {
    (new ReflectionProperty(CachedElementQuery::class, '_cache'))->setValue(null, []);
    (new ReflectionProperty(Ticket::find(), '_cache'))->setValue(null, []);
};
$app->user->setIdentity(null);
$type = Events::$plugin->getEventTypes()->getAllEventTypes()[0];
$siteId = (int)array_key_first($type->getSiteSettings());
$app->sites->setCurrentSite($siteId);
$newOrder = static fn() => new Order([
    'number' => StringHelper::UUID(), 'orderSiteId' => $siteId,
    'storeId' => Commerce::getInstance()->getStores()->getCurrentStore()->id,
]);
$transaction = $app->db->beginTransaction();

try {
    foreach (['advance', 'pending', 'expired', 'disabled', 'disabled_site', 'sale_pending', 'sale_finished', 'no_stock', 'disabled_session', 'disabled_type'] as $case) {
        $event = new Event([
            'typeId' => $type->id, 'siteId' => $siteId,
            'title' => 'Availability regression', 'postDate' => new DateTime('-2 days'),
        ]);
        if ($case === 'pending') {
            $event->postDate = new DateTime('+2 days');
        } elseif ($case === 'expired') {
            $event->expiryDate = new DateTime('-1 day');
        } elseif ($case === 'disabled') {
            $event->enabled = false;
        } elseif ($case === 'disabled_site') {
            $event->enabledForSite = false;
        }
        $save($event);
        $session = new Session([
            'primaryOwner' => $event, 'owner' => $event, 'siteId' => $siteId,
            'startDate' => new DateTime('+6 months'), 'endDate' => new DateTime('+6 months +2 hours'),
            'enabled' => $case !== 'disabled_session',
        ]);
        $save($session);
        $ticketType = new TicketType([
            'primaryOwner' => $event, 'owner' => $event, 'siteId' => $siteId,
            'title' => 'Standard ticket', 'price' => 25, 'capacity' => $case === 'no_stock' ? 0 : 20,
            'enabled' => $case !== 'disabled_type',
        ]);
        if ($case === 'sale_pending') {
            $ticketType->availableFrom = new DateTime('+1 day');
        } elseif ($case === 'sale_finished') {
            $ticketType->availableTo = new DateTime('-1 day');
        }
        $save($ticketType);
        $resetCache();
        $app->sites->setCurrentSite($siteId);
        $event->updateTickets();
        $resetCache();
        $ticket = Ticket::find()->eventId($event->id)->siteId($siteId)->status(null)->one();
        $check($ticket !== null, "$case: the normal ticket generation path must remain usable.");
        $expected = $case === 'advance';
        $check($ticket->getIsAvailable() === $expected, "$case: ticket availability must follow its event and sale conditions.");
        $order = $newOrder();
        $check(Commerce::getInstance()->getPurchasables()->isPurchasableAvailable($ticket, $order) === $expected, "$case: Commerce availability must agree.");
        $lineItem = Commerce::getInstance()->getLineItems()->create($order, ['purchasableId' => $ticket->id, 'qty' => 1]);
        $check($lineItem->refresh() === $expected, "$case: line item refresh must enforce availability.");
        $order->addLineItem($lineItem);
        $order->validateLineItems();
        $check(count($order->getLineItems()) === ($expected ? 1 : 0), "$case: cart validation must remove unavailable tickets.");
        $check(!$order->hasErrors(), "$case: cart normalization must not introduce unrelated validation errors.");

        if (!$expected) {
            continue;
        }

        $check($session->startDate > new DateTime('+5 months'), 'The advance-sale control must be well before the session date.');
        $check($ticket->getPrice() === 25.0, 'Availability must not change the ticket price.');
        $check($ticket->getSession()->getIsAvailable() && $ticket->getType()->getIsAvailable(), 'Published event/session/type availability must remain consistent without recursion.');

        foreach ([false, true] as $provisional) {
            $draft = $app->drafts->createDraft($event, (int)$userId, null, null, [], $provisional);
            $draftTicket = clone $ticket;
            $draftTicket->setEvent($draft);
            $check(!$draftTicket->getIsAvailable(), 'Draft preview owners must not become purchasable events.');
            $check($draftTicket->getPrice() === 25.0, 'Draft previews must retain ticket pricing.');
        }
        $revisionId = $app->revisions->createRevision($event, (int)$userId, null, [], true);
        $resetCache();
        $revision = Event::find()->id($revisionId)->siteId($siteId)->revisions(true)->one();
        $check($revision !== null, 'The revision control must exist.');
        $revisionTicket = clone $ticket;
        $revisionTicket->setEvent($revision);
        $check(!$revisionTicket->getIsAvailable(), 'Historical event revisions must not be purchasable.');

        // Existing carts must recheck availability when the event expires.
        $ticket->getEvent()->expiryDate = new DateTime('-1 minute');
        $order->validateLineItems();
        $check($order->getLineItems() === [], 'An existing cart must remove a ticket when its event expires.');

        // Completed-order normalization must leave historical line items intact.
        $historicalOrder = $newOrder();
        $historicalOrder->addLineItem($lineItem);
        $historicalOrder->isCompleted = true;
        $historicalOrder->validateLineItems();
        $check(count($historicalOrder->getLineItems()) === 1, 'Completed-order line items must remain intact.');
    }
} finally {
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
}

echo "Passed $checks ticket availability checks on PHP " . PHP_VERSION . ".\n";
