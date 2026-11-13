<?php

use craft\elements\User;
use verbb\base\elements\db\CachedElementQuery;
use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\elements\Session;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketCollection;
use verbb\events\elements\TicketType;
use verbb\events\variables\EventsVariable;

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
$user = User::find()->id($userId)->one();
$check($user && !$user->admin, 'Use a non-admin fixture user.');
$save = static function($element) use ($app): void {
    if (!$app->elements->saveElement($element)) {
        throw new RuntimeException(json_encode($element->getErrors()));
    }
};
$resetCache = static function(): void {
    (new ReflectionProperty(CachedElementQuery::class, '_cache'))->setValue(null, []);
    (new ReflectionProperty(Ticket::find(), '_cache'))->setValue(null, []);
};
$ids = static fn(array $elements): array => array_map(static fn($element) => $element->id, $elements);
$variable = new EventsVariable();
$eventTypes = Events::$plugin->getEventTypes()->getAllEventTypes();
$check(count($eventTypes) >= 2, 'Two event types are required.');
[$type, $otherType] = $eventTypes;
// Nested permissions are only kept alongside their parents.
$grant = static function(array $permissions) use ($app, $user, $type): User {
    $base = ['accessCp', 'accessPlugin-events', 'events-viewEvents:' . $type->uid, 'events-viewTicketTypes:' . $type->uid];
    $app->userPermissions->saveUserPermissions($user->id, array_merge($base, $permissions));
    $app->user->setIdentity($user);

    return $user;
};
$siteId = (int)array_key_first($type->getSiteSettings());
$app->sites->setCurrentSite($siteId);
$transaction = $app->db->beginTransaction();

try {
    $app->users->assignUserToGroups($user->id, []);

    // Guests have no identity, so permission-based helpers return nothing rather than erroring.
    $app->user->setIdentity(null);
    $check($variable->getEditableEventTypes() === [], 'Guests must receive no editable event types.');
    $check(Events::$plugin->getEventTypes()->getViewableEventTypes() === [], 'Guests must receive no viewable event types.');
    $check(Events::$plugin->getEventTypes()->getCreatableEventTypes() === [], 'Guests must receive no creatable event types.');
    $check($variable->getEditableTicketTypes() === [], 'Guests must receive no editable ticket types.');

    $event = new Event(['typeId' => $type->id, 'siteId' => $siteId, 'title' => 'Twig variable regression', 'postDate' => new DateTime('-1 day')]);
    $save($event);
    $session = new Session(['primaryOwner' => $event, 'owner' => $event, 'siteId' => $siteId, 'startDate' => new DateTime('+1 month'), 'endDate' => new DateTime('+1 month +2 hours')]);
    $save($session);
    $ticketTypes = [];

    foreach (['Available' => [true, 20], 'Sold out' => [true, 0], 'Disabled' => [false, 20]] as $title => [$enabled, $capacity]) {
        $ticketType = new TicketType(['primaryOwner' => $event, 'owner' => $event, 'siteId' => $siteId, 'title' => $title, 'price' => 25, 'capacity' => $capacity, 'enabled' => $enabled]);
        $save($ticketType);
        $ticketTypes[$title] = $ticketType->id;
    }

    $otherEvent = new Event(['typeId' => $otherType->id, 'siteId' => $siteId, 'title' => 'Twig variable regression (other type)', 'postDate' => new DateTime('-1 day')]);
    $save($otherEvent);
    $otherTicketType = new TicketType(['primaryOwner' => $otherEvent, 'owner' => $otherEvent, 'siteId' => $siteId, 'title' => 'Other', 'price' => 25, 'capacity' => 20]);
    $save($otherTicketType);
    $resetCache();
    $event = Event::find()->id($event->id)->siteId($siteId)->one();
    $event->updateTickets();
    $resetCache();

    // `getTicketTypes()` lists enabled ticket types across every event.
    $all = $variable->getTicketTypes();
    $check(array_filter($all, static fn($ticketType) => !$ticketType instanceof TicketType) === [], 'getTicketTypes() must return ticket type elements.');
    $check(in_array($ticketTypes['Available'], $ids($all), true) && in_array($otherTicketType->id, $ids($all), true), 'getTicketTypes() must include ticket types from every event type.');
    $check(!in_array($ticketTypes['Disabled'], $ids($all), true), 'getTicketTypes() must leave out disabled ticket types.');

    // `getEditableTicketTypes()` follows each owner event type's ticket type permission, including disabled ticket types.
    $grant(['events-editEvents:' . $type->uid, 'events-editTicketTypes:' . $type->uid]);
    $check($user->can('events-editTicketTypes:' . $type->uid), 'The fixture user must hold the ticket type permission.');
    $editable = $ids($variable->getEditableTicketTypes());
    $check(array_diff($ticketTypes, $editable) === [], 'Editable ticket types must include every ticket type, enabled or not, for a permitted event type.');
    $check(!in_array($otherTicketType->id, $editable, true), 'Editable ticket types must exclude event types without ticket type permission.');
    $check($ids($variable->getEditableEventTypes()) === [$type->id], 'Editable event types must follow event permissions.');
    $grant(['events-editEvents:' . $type->uid]);
    $check(!array_intersect($ticketTypes, $ids($variable->getEditableTicketTypes())), 'Event permission alone must not make ticket types editable.');

    // The deprecated `availableTickets(eventId)` matches the event's own method and logs a deprecation.
    $app->user->setIdentity(null);
    $deprecations = count($app->getDeprecator()->getRequestLogs());
    $available = $variable->availableTickets($event->id);
    $expected = $event->getAvailableTickets();
    $check($available instanceof TicketCollection, 'availableTickets() must return a ticket collection.');
    $check($expected->count() === 1 && $expected->first()->getType()->id === $ticketTypes['Available'], 'The fixture must have exactly one available ticket.');
    $check($available->map(fn($ticket) => $ticket->id)->values()->all() === $expected->map(fn($ticket) => $ticket->id)->values()->all(), 'availableTickets() must match event.getAvailableTickets().');
    $check(count($app->getDeprecator()->getRequestLogs()) > $deprecations, 'availableTickets() must log a deprecation.');
    $check($variable->availableTickets(PHP_INT_MAX)->isEmpty(), 'availableTickets() must return an empty collection for a missing event.');
} finally {
    $transaction->rollBack();
}

echo "Passed $checks Twig variable regression checks on PHP " . PHP_VERSION . ".\n";
