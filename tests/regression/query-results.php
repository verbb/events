<?php

use craft\helpers\Db;
use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\Session;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;
use verbb\events\helpers\ParentElements;
use yii\queue\ExecEvent;
use yii\queue\Queue;

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
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';

require $appPath . '/bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/web.php';

$checks = 0;
$check = static function(bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};
$check($app->db->createCommand($app->db->getIsPgsql() ? 'SELECT current_database()' : 'SELECT DATABASE()')->queryScalar() === $database, 'An explicitly named disposable database is required.');
$save = static function($element) use ($app): void {
    if (!$app->elements->saveElement($element)) {
        throw new RuntimeException(json_encode($element->getErrors()));
    }
};
$type = Events::$plugin->getEventTypes()->getAllEventTypes()[0] ?? null;
$check($type !== null, 'An event type is required.');
$siteId = (int)array_key_first($type->getSiteSettings());
$app->sites->setCurrentSite($siteId);
$app->user->setIdentity(null);
$transaction = $app->db->beginTransaction();

try {
    $event = new Event(['typeId' => $type->id, 'siteId' => $siteId, 'title' => 'Query results regression', 'postDate' => new DateTime('-1 day')]);
    $save($event);

    foreach ([1, 2, 3] as $month) {
        $save(new Session(['primaryOwner' => $event, 'owner' => $event, 'siteId' => $siteId, 'startDate' => new DateTime("+$month month"), 'endDate' => new DateTime("+$month month +2 hours")]));
    }

    foreach (['Standard', 'VIP'] as $title) {
        $save(new TicketType(['primaryOwner' => $event, 'owner' => $event, 'siteId' => $siteId, 'title' => $title, 'price' => 25, 'capacity' => 20]));
    }

    // Running the sync first warms any per-process query results before the event changes below.
    $event->updateTickets();
    $tickets = Ticket::find()->eventId($event->id)->status(null)->all();
    $check(count($tickets) === 6, 'Three sessions and two ticket types generate six tickets.');

    foreach ($tickets as $ticket) {
        $save(new PurchasedTicket(['eventId' => $event->id, 'sessionId' => $ticket->sessionId, 'ticketTypeId' => $ticket->typeId, 'ticketId' => $ticket->id, 'checkedIn' => false]));
    }

    // Each query must return what its own SQL selects, including Yii query state outside Craft's criteria.
    $queries = [
        'events' => static fn() => Event::find()->id($event->id)->status(null),
        'sessions' => static fn() => Session::find()->eventId($event->id)->status(null),
        'ticket types' => static fn() => TicketType::find()->eventId($event->id)->status(null),
        'tickets' => static fn() => Ticket::find()->eventId($event->id)->status(null),
        'purchased tickets' => static fn() => PurchasedTicket::find()->eventId($event->id)->status(null),
    ];

    foreach ($queries as $label => $query) {
        $all = $query()->orderBy(['elements.id' => SORT_ASC])->all();
        $total = count($all);
        $check($total > 0, "The $label fixture exists.");
        $check(count($query()->limit(1)->all()) === 1, "Limiting $label returns one row.");
        $check(count($query()->limit(10)->all()) === $total, "A larger limit on $label returns every row after a smaller one.");
        $check(count($query()->offset(1)->all()) === $total - 1, "Offsetting $label skips a row.");

        // Tickets always follow their session and ticket type order.
        if ($label !== 'tickets') {
            $check($query()->orderBy(['elements.id' => SORT_DESC])->one()?->id === end($all)->id, "Reversing the order of $label changes the first row.");
        }

        $check(is_array($query()->asArray()->one()), "Array results for $label are arrays.");
        $check($query()->andWhere(['elements.id' => $all[0]->id])->ids() === [$all[0]->id], "A custom condition on $label filters its IDs.");
        $check(count($query()->andWhere(['elements.id' => $all[0]->id])->all()) === 1, "A custom condition on $label filters its elements.");
        $check((int)$query()->andWhere(['elements.id' => 0])->count() === 0 && !$query()->andWhere(['elements.id' => 0])->exists(), "A custom condition on $label filters counts.");
        $check($query()->one() !== $query()->one(), "Separate $label queries return separate instances.");
    }

    // A write made outside the element service must be visible to the next query in the same process.
    $purchase = PurchasedTicket::find()->eventId($event->id)->one();
    $check(!$purchase->checkedIn, 'The fixture purchase starts unchecked.');
    Db::update('{{%events_purchased_tickets}}', ['checkedIn' => true], ['id' => $purchase->id]);
    $check((bool)PurchasedTicket::find()->id($purchase->id)->one()->checkedIn, 'A direct update is visible to the next purchased ticket query.');

    $savepoint = $app->db->beginTransaction();
    Db::update('{{%events_purchased_tickets}}', ['checkedIn' => false], ['id' => $purchase->id]);
    $check(!PurchasedTicket::find()->id($purchase->id)->one()->checkedIn, 'An update inside a nested transaction is visible.');
    $savepoint->rollBack();
    $check((bool)PurchasedTicket::find()->id($purchase->id)->one()->checkedIn, 'A rolled-back update is not served afterward.');

    // Rows from one query share the parents loaded for them; nothing is kept for later queries.
    $batch = Ticket::find()->eventId($event->id)->status(null)->all();
    $check($batch[0]->getEvent() === $batch[1]->getEvent(), 'Tickets from one query share their batched event.');
    $check(Ticket::find()->id($batch[0]->id)->status(null)->one()->getEvent() !== $batch[0]->getEvent(), 'A later ticket query loads its own event.');
    $check(ParentElements::get(Event::class, $event->id) === false, 'Batched parents are only available while a query populates.');
    $purchases = PurchasedTicket::find()->eventId($event->id)->status(null)->all();
    $check($purchases[0]->getSession()?->id === $purchases[0]->sessionId && $purchases[0]->getTicket()?->id === $purchases[0]->ticketId && $purchases[0]->getTicketType()?->id === $purchases[0]->ticketTypeId, 'Purchased tickets receive their own batched parents.');

    // Parents are loaded once per query, not once per row.
    $app->db->enableProfiling = true;
    $logger = Yii::getLogger();
    $logger->flushInterval = PHP_INT_MAX;
    $queryCount = static function(callable $run) use ($logger): int {
        // Run once first so table schema lookups, which take more queries on PostgreSQL, aren't counted.
        $run();
        $logger->messages = [];
        $run();

        return $logger->getDbProfiling()[0];
    };
    $ticketQueries = $queryCount(static function() use ($event): void {
        foreach (Ticket::find()->eventId($event->id)->status(null)->all() as $ticket) {
            $ticket->getEvent();
            $ticket->getSession();
            $ticket->getType();
        }
    });
    $check($ticketQueries <= 5, "Six tickets and their parents load in five queries or fewer ($ticketQueries).");
    $purchaseQueries = $queryCount(static function() use ($event): void {
        foreach (PurchasedTicket::find()->eventId($event->id)->status(null)->all() as $purchase) {
            $purchase->getEvent();
            $purchase->getSession();
            $purchase->getTicket();
            $purchase->getTicketType();
        }
    });
    $check($purchaseQueries <= 6, "Six purchased tickets and their parents load in six queries or fewer ($purchaseQueries).");
    $eagerQueries = $queryCount(static function() use ($event): void {
        foreach (Event::find()->id($event->id)->with(['tickets'])->one()->getTickets(true) as $ticket) {
            $ticket->getEvent();
            $ticket->getSession();
            $ticket->getType();
        }
    });
    $check($eagerQueries <= 7, "Eager-loaded tickets load their parents in one batch ($eagerQueries queries).");

    // Purchase counts are only kept outside transactions, and are dropped on writes and at queue job boundaries.
    $service = Events::$plugin->getPurchasedTickets();
    $kept = new ReflectionProperty($service, '_counts');
    $ticket = $batch[0];
    $check($ticket->getPurchasedTicketsCount() === 1 && $kept->getValue($service) === [], 'Counts read inside a transaction are correct and not kept.');
    $kept->setValue($service, ['ticketId' => [$ticket->id => 5]]);
    $check($ticket->getPurchasedTicketsCount() === 5, 'Kept counts are reused.');
    $save(new PurchasedTicket(['eventId' => $event->id, 'sessionId' => $ticket->sessionId, 'ticketTypeId' => $ticket->typeId, 'ticketId' => $ticket->id, 'checkedIn' => false]));
    $check($kept->getValue($service) === [] && $ticket->getPurchasedTicketsCount() === 2, 'Saving a purchased ticket drops kept counts.');
    $kept->setValue($service, ['sessionId' => [$ticket->sessionId => 5]]);
    $app->getQueue()->trigger(Queue::EVENT_BEFORE_EXEC, new ExecEvent());
    $check($kept->getValue($service) === [], 'Each queue job starts without kept counts.');

    // Session dates come from live sessions, and anything that names them still sorts and filters on them.
    $firstStart = Session::find()->eventId($event->id)->orderBy(['startDate' => SORT_ASC])->one()->startDate;
    $lastEnd = Session::find()->eventId($event->id)->orderBy(['endDate' => SORT_DESC])->one()->endDate;
    $loaded = Event::find()->id($event->id)->one();
    $check($loaded->startDate?->getTimestamp() === $firstStart->getTimestamp() && $loaded->endDate?->getTimestamp() === $lastEnd->getTimestamp(), 'Events load their first session start and last session end.');
    $check(Event::find()->id($event->id)->asArray()->one()['startDate'] !== null, 'Array results include session dates.');
    $check(Event::find()->id($event->id)->orderBy('startDate asc')->ids() === [$event->id], 'IDs can be ordered by session dates.');
    $check(Event::find()->id($event->id)->orderBy('sessions.endDate desc')->one()?->id === $event->id, 'Orders can name the sessions alias.');
    $check(Event::find()->id($event->id)->andWhere(['<', 'startDate', Db::prepareDateForDb(new DateTime('+2 weeks'))])->count() == 0, 'Custom conditions can name session dates.');
    $check(Event::find()->id($event->id)->startDate('>= ' . (new DateTime('+2 weeks'))->format('Y-m-d'))->exists(), 'Start date criteria match the first session.');
    $check(!Event::find()->id($event->id)->endDate('< ' . (new DateTime('+2 months'))->format('Y-m-d'))->exists(), 'End date criteria match the last session.');

    // A second sync in the same process sees nested elements added after the first one.
    $save(new Session(['primaryOwner' => $event, 'owner' => $event, 'siteId' => $siteId, 'startDate' => new DateTime('+4 month'), 'endDate' => new DateTime('+4 month +2 hours')]));
    $event->updateTickets();
    $check((int)Ticket::find()->eventId($event->id)->status(null)->count() === 8, 'A second sync generates tickets for a newly added session.');
    $check(count(Session::find()->eventId($event->id)->status(null)->all()) === 4, 'Session queries see the added session.');
} finally {
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
}

echo "Passed $checks query result checks on PHP " . PHP_VERSION . ".\n";
