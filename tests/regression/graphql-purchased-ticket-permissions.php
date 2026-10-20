<?php

use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use verbb\base\elements\db\CachedElementQuery;
use verbb\events\Events;
use verbb\events\controllers\TicketsController;
use verbb\events\elements\Event;
use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\Session;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;
use verbb\events\gql\resolvers\PurchasedTicketResolver;
use verbb\events\helpers\Gql;
use verbb\events\migrations\m261020_000000_purchased_ticket_graphql_permissions;

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
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_SERVER['REQUEST_URI'] = '/actions/graphql/api';
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
$types = array_slice(Events::$plugin->getEventTypes()->getAllEventTypes(), 0, 2);
$check(count($types) === 2, 'Two event types are required.');
$siteId = (int)array_key_first($types[0]->getSiteSettings());
$app->sites->setCurrentSite($siteId);
$app->user->setIdentity(null);
$app->request->setIsCpRequest(false);
$reset = static function(): void {
    (new ReflectionProperty(CachedElementQuery::class, '_cache'))->setValue(null, []);
};
$save = static function($element) use ($app): void {
    if (!$app->elements->saveElement($element)) {
        throw new RuntimeException('Fixture save failed: ' . json_encode($element->getErrors()));
    }
};
$scope = ['sites.' . $app->sites->getSiteById($siteId)->uid . ':read'];
foreach ($types as $type) {
    $scope[] = 'eventsEventTypes.' . $type->uid . ':read';
    $scope[] = 'eventsPurchasedTickets.' . $type->uid . ':read';
}
$activate = static function(array $permissions) use ($app, $reset): GqlSchema {
    $schema = new GqlSchema(['name' => 'Purchased ticket permissions', 'uid' => StringHelper::UUID(), 'isPublic' => true, 'scope' => $permissions]);
    $app->gql->flushCaches();
    $app->gql->setActiveSchema($schema);
    $reset();
    return $schema;
};
$execute = static function(string $query, array $variables = []) use ($app, $reset, $check): array {
    $reset();
    $result = $app->gql->executeQuery($app->gql->getActiveSchema(), $query, $variables, null, true);
    $check(empty($result['errors']), 'GraphQL execution failed: ' . json_encode($result['errors'] ?? []));
    return $result['data'];
};
$settings = Events::$plugin->getSettings();
$previousLogin = $settings->checkinLogin;
$previousCache = $app->config->general->enableGraphQlCaching;
$formats = array_map(static fn($type) => $type->purchasedTicketTitleFormat, $types);
$transaction = $app->db->beginTransaction();
try {
    $purchases = [];
    $events = [];
    foreach ($types as $index => $type) {
        $check(isset($type->getSiteSettings()[$siteId]), 'Both event types must support the fixture site.');
        $event = new Event(['title' => 'GraphQL permission fixture', 'typeId' => $type->id, 'siteId' => $siteId, 'postDate' => new DateTime('-1 day')]);
        $save($event);
        $session = new Session(['primaryOwner' => $event, 'owner' => $event, 'siteId' => $siteId, 'startDate' => new DateTime('+1 day'), 'endDate' => new DateTime('+1 day +1 hour')]);
        $save($session);
        $ticketType = new TicketType(['primaryOwner' => $event, 'owner' => $event, 'siteId' => $siteId, 'title' => 'Standard', 'price' => 25, 'capacity' => 20]);
        $save($ticketType);
        $reset();
        $event->updateTickets();
        $reset();
        $ticket = Ticket::find()->eventId($event->id)->siteId($siteId)->one();
        $check($ticket !== null, 'Ticket generation must succeed.');
        $purchase = new PurchasedTicket(['eventId' => $event->id, 'sessionId' => $session->id, 'ticketTypeId' => $ticketType->id, 'ticketId' => $ticket->id, 'checkedIn' => false]);
        $save($purchase);
        $purchases[] = $purchase;
        $events[] = $event;
    }
    $ids = array_map(static fn($purchase) => $purchase->id, $purchases);
    $query = 'query($ids:[QueryArgument]) { rows: eventsPurchasedTickets(id:$ids,orderBy:"id ASC") { id identifier: uid title checkedIn ...Details } } fragment Details on PurchasedTicketInterface { reservationStatus }';
    $activate($scope);
    $app->gql->getSchemaDef($app->gql->getActiveSchema(), true)->assertValid();
    $rows = $execute($query, ['ids' => $ids])['rows'];
    $check(count($rows) === 2, 'Read permissions must retain both event types.');
    foreach ($rows as $row) {
        $check($row['identifier'] === null && $row['title'] === null, 'Ordinary read access must not disclose protected fields.');
        $check($row['checkedIn'] === false && $row['reservationStatus'] === 'active', 'Ordinary ticket metadata must remain readable.');
    }
    $grant = 'eventsPurchasedTickets.' . $types[0]->uid . ':checkIn';
    $activate([...$scope, $grant]);
    $rows = $execute($query, ['ids' => $ids])['rows'];
    $check($rows[0]['identifier'] === $purchases[0]->uid && $rows[0]['title'] !== null, 'The explicit grant must preserve the original identifier and title.');
    $check($rows[1]['identifier'] === null && $rows[1]['title'] === null, 'A grant for one event type must not affect another.');
    foreach ([$scope, [...$scope, $grant]] as $index => $permissions) {
        $activate($permissions);
        foreach ([$purchases[0]->uid, ['not', $purchases[1]->uid], 'not ' . $purchases[1]->uid] as $criteria) {
            $data = $execute('query($ids:[QueryArgument],$uid:[String]) { rows: eventsPurchasedTickets(id:$ids,uid:$uid) { id } one: eventsPurchasedTicket(id:$ids,uid:$uid) { id } count: eventsPurchasedTicketCount(id:$ids,uid:$uid) }', ['ids' => $ids, 'uid' => (array)$criteria]);
            $check(count($data['rows']) === $index && $data['count'] === $index, 'UID criteria must remain within the identifier permission boundary.');
            $check($index ? (int)$data['one']['id'] === $purchases[0]->id : $data['one'] === null, 'Single queries must enforce identifier permission.');
        }
        foreach (['uid ASC', 'elements.uid DESC', 'id ASC, elements.UID DESC'] as $order) {
            $data = $execute('query($ids:[QueryArgument],$order:String) { rows: eventsPurchasedTickets(id:$ids,orderBy:$order) { id } count: eventsPurchasedTicketCount(id:$ids,orderBy:$order) }', ['ids' => $ids, 'order' => $order]);
            $check(count($data['rows']) === $index && $data['count'] === $index, 'UID ordering must enforce identifier permission.');
        }
        $data = $execute('query($ids:[QueryArgument]) { eventsEvents(id:$ids,orderBy:"id ASC") { purchasedTickets { id uid title } } }', ['ids' => array_map(static fn($event) => $event->id, $events)]);
        foreach ($data['eventsEvents'] as $eventIndex => $row) {
            $check(count($row['purchasedTickets']) === 1, 'Nested event purchases must remain available.');
            $expected = $index === 1 && $eventIndex === 0 ? $purchases[0]->uid : null;
            $check(($row['purchasedTickets'][0]['uid'] ?? null) === $expected, 'Nested event purchases must apply per-row identifier permission.');
        }
    }
    foreach (['{uid}', '{{ uid|upper }}'] as $format) {
        $types[0]->purchasedTicketTitleFormat = $format;
        $activate([...$scope, $grant]);
        $row = $execute($query, ['ids' => [$ids[0]]])['rows'][0];
        $check(strtolower($row['title']) === $purchases[0]->uid, 'The fixture must exercise a title derived from the identifier.');
        $activate($scope);
        $row = $execute($query, ['ids' => [$ids[0]]])['rows'][0];
        $check($row['title'] === null, 'Configured title transformations must not bypass permission.');
    }
    $types[0]->purchasedTicketTitleFormat = $formats[0];
    foreach ([
        [$grant],
        ['eventsEventTypes.' . $types[0]->uid . ':read', $grant],
        ['eventsPurchasedTickets.' . $types[0]->uid . ':read', $grant],
        [...$scope, 'eventsPurchasedTickets.' . $types[0]->uid . ':checkInExtra'],
        [...$scope, 'eventsPurchasedTickets.' . $types[0]->uid . ':write'],
    ] as $permissions) {
        $activate($permissions);
        $check(Gql::getAllowedPurchasedTicketEventTypeIds(true) === [], 'Partial or wrong-action grants must not authorize identifiers.');
        $prepared = PurchasedTicketResolver::prepareQuery(null, ['uid' => $purchases[0]->uid]);
        $check($prepared->count() === 0, 'Partial grants must fail closed for criteria.');
    }
    $schema = $activate($scope);
    $app->config->general->enableGraphQlCaching = true;
    $cacheQuery = '{ eventsPurchasedTicket(id:[' . $ids[0] . ']) { uid } }';
    $key = (new ReflectionMethod($app->gql, '_getCacheKey'))->invoke($app->gql, $schema, $cacheQuery, null, [], null);
    $check(is_string($key), 'GraphQL result caching must be enabled for the upgrade test.');
    $legacyResult = ['data' => ['eventsPurchasedTicket' => ['uid' => $purchases[0]->uid]]];
    $app->gql->setCachedResult($key, $legacyResult);
    $check($app->gql->getCachedResult($key) === $legacyResult, 'A legacy cached response must exist before migration.');
    $migration = new m261020_000000_purchased_ticket_graphql_permissions();
    $check($migration->safeUp(), 'The upgrade migration must succeed.');
    $check($app->gql->getCachedResult($key) === null, 'The upgrade must invalidate legacy responses.');
    $check($execute($cacheQuery)['eventsPurchasedTicket']['uid'] === null, 'The query must resolve with the new permissions after upgrade.');
    $activate([...$scope, $grant]);
    $check($execute($cacheQuery)['eventsPurchasedTicket']['uid'] === $purchases[0]->uid, 'Granting access must preserve identifiers with caching enabled.');
    $activate($scope);
    $check($execute($cacheQuery)['eventsPurchasedTicket']['uid'] === null, 'Rebuilding a restricted schema must not reuse privileged results.');

    $trusted = PurchasedTicket::find()->id($ids[0])->one();
    $check($trusted->uid === $purchases[0]->uid && $trusted->getDynamicTitle() !== null, 'Trusted PHP must retain identifiers and dynamic titles.');
    $url = $trusted->getCheckInUrl();
    $check(str_contains($url, $trusted->uid) && $trusted->getQrCode() !== '', 'Existing check-in URLs and QR generation must remain available.');
    $check($app->view->renderString('{{ ticket.uid }}', ['ticket' => $trusted]) === $trusted->uid, 'Trusted Twig must retain identifier access.');
    $checkIn = static function() use ($app, $trusted, $reset): array {
        $reset();
        $app->controller = null;
        $app->request->setQueryParams([]);
        $app->request->setBodyParams(['uid' => $trusted->uid, 'confirm' => true, $app->request->csrfParam => $app->request->getCsrfToken()]);
        return (new TicketsController('tickets', Events::$plugin))->runAction('check-in')->data;
    };
    $settings->checkinLogin = true;
    $check(isset($checkIn()['error']), 'Login-required check-in must still reject guests.');
    $settings->checkinLogin = false;
    $check($checkIn()['success'] ?? false, 'Existing anonymous check-in links must remain usable when configured.');
    $reset();
    $check(PurchasedTicket::find()->id($ids[0])->one()->checkedIn, 'The existing link must update stored check-in state.');
} finally {
    $settings->checkinLogin = $previousLogin;
    $app->config->general->enableGraphQlCaching = $previousCache;
    foreach ($types as $index => $type) {
        $type->purchasedTicketTitleFormat = $formats[$index];
    }
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
    $app->gql->invalidateCaches();
}
echo "Passed $checks purchased-ticket GraphQL permission checks on PHP " . PHP_VERSION . ".\n";
