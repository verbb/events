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
use verbb\events\helpers\GqlRelations;
use verbb\events\elements\db\TicketQuery;
use verbb\events\migrations\m261020_000001_purchased_ticket_graphql_relations;
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
    (new ReflectionProperty(TicketQuery::class, '_cache'))->setValue(null, []);
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
    $query = 'query($purchase:[QueryArgument],$event:[QueryArgument]) {
        direct:eventsEvents(id:$event) { id }
        count:eventsPurchasedTicketCount(id:$purchase)
        one:eventsPurchasedTicket(id:$purchase) { id }
        purchases:eventsPurchasedTickets(id:$purchase) {
            id reservationStatus
            ...Relations
        }
    }
    fragment Relations on PurchasedTicketInterface {
        event { id title siteId }
        session { id enabled event { id } }
        ticketType { id enabled event { id } }
        ticket { id enabled event { id } session { id enabled } type { id enabled } }
    }';
    $owner = $events[0];
    $purchase = $purchases[0];
    $variables = ['purchase'=>[$ids[0]], 'event'=>[$owner->id]];
    foreach (['published', 'disabled', 'pending', 'expired'] as $state) {
        $owner->enabled = $state !== 'disabled';
        $owner->postDate = new DateTime($state === 'pending' ? '+2 days' : '-2 days');
        $owner->expiryDate = $state === 'expired' ? new DateTime('-1 day') : null;
        $save($owner);
        foreach ([false, true] as $inactive) {
            $activate($inactive ? [...$scope, 'elements.inactive:read'] : $scope);
            $currentQuery = $inactive ? str_replace('eventsEvents(id:$event)', 'eventsEvents(id:$event,status:null)', $query) : $query;
            $data = $execute($currentQuery, $variables);
            $visible = $state === 'published' || $inactive;
            $check(count($data['direct']) === ($visible ? 1 : 0), 'Direct event query establishes the visibility control.');
            $check(count($data['purchases']) === 1 && $data['count'] === 1 && (int)$data['one']['id'] === $purchase->id, 'Owner publication must not remove purchase history or counts.');
            foreach (['event','session','ticketType','ticket'] as $relation) {
                $check(($data['purchases'][0][$relation] !== null) === $visible, 'Purchase relation must respect owner visibility: ' . $state . '/' . $relation);
            }
            $trusted = PurchasedTicket::find()->id($purchase->id)->one();
            $check($trusted->getEvent() !== null && $trusted->getSession() !== null && $trusted->getTicketType() !== null, 'Trusted getters retain historical relations.');
            $check($app->view->renderString('{{ ticket.event.title }}', ['ticket'=>$trusted]) === $owner->title, 'Trusted Twig retains historical event access.');
        }
    }
    $owner->enabled = true;
    $owner->postDate = new DateTime('-2 days');
    $owner->expiryDate = null;
    $save($owner);
    foreach (['session'=>'sessionId', 'ticketType'=>'ticketTypeId', 'ticket'=>'ticketId'] as $relation => $attribute) {
        $app->db->createCommand()->update('{{%elements}}', ['enabled'=>false], ['id'=>$purchase->$attribute])->execute();
        foreach ([false, true] as $inactive) {
            $activate($inactive ? [...$scope, 'elements.inactive:read'] : $scope);
            $row = $execute($query, $variables)['purchases'][0];
            $check(($row[$relation] !== null) === $inactive, 'Independently disabled child must require inactive access.');
            $check(($row['ticket'] !== null) === $inactive, 'Generated ticket must inherit child visibility.');
            $direct = $execute('query($id:[QueryArgument]) { eventsTickets(id:$id) { event { id } session { id } type { id } } }', ['id'=>[$purchase->ticketId]]);
            if ($relation !== 'ticket') {
                $field = $relation === 'ticketType' ? 'type' : $relation;
                $check(($direct['eventsTickets'][0][$field] !== null) === $inactive, 'Deeper ticket relations must use the same visibility boundary.');
            }
        }
        $app->db->createCommand()->update('{{%elements}}', ['enabled'=>true], ['id'=>$purchase->$attribute])->execute();
    }

    // Reusing a loaded relation under a different schema must not reuse its permission decision.
    $activate([...$scope, 'elements.inactive:read']);
    $loaded = $purchase->getSession();
    $check(GqlRelations::visible($loaded) === $loaded, 'Allowed loaded session remains the same object.');
    $noSites = array_values(array_filter($scope, static fn($permission)=>!str_starts_with($permission, 'sites.')));
    $activate($noSites);
    $check(GqlRelations::visible($loaded) === null, 'An already-loaded relation requires site permission.');
    $activate(['sites.'.$app->sites->getSiteById($siteId)->uid.':read', 'eventsEventTypes.'.$types[1]->uid.':read', 'eventsPurchasedTickets.'.$types[1]->uid.':read']);
    $check(GqlRelations::visible($loaded) === null, 'An already-loaded relation requires its event-type permission.');
    $check($execute('query($id:[QueryArgument]) { eventsPurchasedTickets(id:$id) { id } }', ['id'=>[$purchase->id]])['eventsPurchasedTickets'] === [], 'Other event types remain excluded.');

    $sites = array_map('intval', array_keys($types[0]->getSiteSettings()));
    $check(count($sites) >= 2, 'Two sites are required.');
    $otherSiteId = array_values(array_diff($sites, [$siteId]))[0];
    $otherSiteScope = array_values(array_filter($scope, static fn($permission)=>!str_starts_with($permission, 'sites.')));
    $otherSiteScope[] = 'sites.'.$app->sites->getSiteById($otherSiteId)->uid.':read';
    $app->sites->setCurrentSite($otherSiteId);
    $reset();
    $translated = Event::find()->id($owner->id)->siteId($otherSiteId)->one();
    $check($translated !== null, 'The event must propagate to the other site.');
    $translated->title = 'Localized purchase event';
    $save($translated);
    $activate($otherSiteScope);
    $row = $execute($query, $variables)['purchases'][0];
    $check((int)$row['event']['siteId'] === $otherSiteId && $row['event']['title'] === $translated->title, 'Relations retain current-site localization.');
    $check(GqlRelations::visible($translated) === $translated, 'The permitted translation primes the loaded-object check.');
    $app->gql->flushCaches();
    $app->gql->setActiveSchema(new GqlSchema(['name'=>'Changed site scope', 'uid'=>StringHelper::UUID(), 'scope'=>$scope]));
    $check(GqlRelations::visible($translated) === null, 'Changing schema without clearing query caches must not retain site access.');
    $check(GqlRelations::visible($owner) === $owner, 'The permitted primary translation remains readable with warm caches.');
    $app->gql->flushCaches();
    $app->gql->setActiveSchema(new GqlSchema(['name'=>'No event scope', 'uid'=>StringHelper::UUID(), 'scope'=>['sites.'.$app->sites->getSiteById($siteId)->uid.':read']]));
    $check(GqlRelations::visible($owner) === null, 'Changing schema without clearing query caches must not retain event-type access.');
    $activate($scope);
    $row = $execute($query, $variables)['purchases'][0];
    foreach (['event','session','ticketType','ticket'] as $relation) {
        $check($row[$relation] === null, 'Relations must not substitute an allowed site for an unauthorized loaded translation.');
    }
    $translated->enabledForSite = false;
    $save($translated);
    foreach ([false, true] as $inactive) {
        $activate($inactive ? [...$otherSiteScope, 'elements.inactive:read'] : $otherSiteScope);
        $row = $execute($query, $variables)['purchases'][0];
        $check(($row['event'] !== null) === $inactive, 'Localized publication state must be enforced.');
    }
    $app->sites->setCurrentSite($siteId);

    $userId = (int)getenv('VERBB_TEST_CP_USER_ID');
    $check($userId > 0, 'A fixture user is required for draft and revision checks.');
    $drafts = [];
    foreach ([false, true] as $provisional) {
        $draft = $app->drafts->createDraft($owner, $userId, null, null, [], $provisional);
        $draft->title = 'Draft purchase event';
        $save($draft);
        $drafts[] = $draft;
        $reset();
        $draftSession = Session::find()->ownerId($draft->id)->siteId($siteId)->one();
        $draftType = TicketType::find()->ownerId($draft->id)->siteId($siteId)->one();
        $check($draftSession !== null && $draftType !== null, 'Draft must retain nested elements.');
        foreach ([$draft, $draftSession, $draftType] as $element) {
            $activate($scope);
            $check(GqlRelations::visible($element) === null, 'Loaded draft relations require draft permission.');
            $activate([...$scope, 'elements.drafts:read']);
            $check(GqlRelations::visible($element) === $element, 'Explicit draft access retains named and provisional relations.');
        }
    }
    $revisionId = $app->revisions->createRevision($owner, $userId, null, [], true);
    $reset();
    $revision = Event::find()->id($revisionId)->siteId($siteId)->revisions(true)->one();
    $check($revision !== null, 'A real revision is required.');
    $activate($scope);
    $check(GqlRelations::visible($revision) === null, 'Loaded revisions require explicit access.');
    $activate([...$scope, 'elements.revisions:read']);
    $check(GqlRelations::visible($revision) === $revision, 'Revision permission retains access.');

    $activate($scope);
    $app->config->general->enableGraphQlCaching = true;
    $cacheQuery = '{ eventsPurchasedTicket(id:['.$purchase->id.']) { event { id } } }';
    $schema = $app->gql->getActiveSchema();
    $key = (new ReflectionMethod($app->gql, '_getCacheKey'))->invoke($app->gql, $schema, $cacheQuery, null, [], null);
    $check(is_string($key), 'Result caching must be available.');
    $app->gql->setCachedResult($key, ['data'=>['eventsPurchasedTicket'=>['event'=>['id'=>$owner->id]]]]);
    $migration = new m261020_000001_purchased_ticket_graphql_relations();
    $check($migration->safeUp() && $app->gql->getCachedResult($key) === null, 'Upgrade invalidates old relation responses.');
    $app->config->general->enableGraphQlCaching = false;

    // Registered previews retain their owner relationships without granting access to other drafts.
    $activate($scope);
    $preview = $drafts[0];
    $preview->previewing = true;
    $app->elements->setPlaceholderElement($preview);
    $check(GqlRelations::visible($preview) === $preview, 'Authorized preview placeholder remains available.');
    foreach ($preview->getSessions()->all() as $previewSession) {
        $check(GqlRelations::visible($previewSession) === $previewSession, 'Preview sessions retain their authorized owner relationship.');
    }
    $previewRow = $execute($query, $variables)['purchases'][0];
    $check((int)$previewRow['event']['id'] === $preview->id, 'Purchase GraphQL resolves the registered preview owner.');
    foreach (['session', 'ticketType', 'ticket'] as $relation) {
        $check($previewRow[$relation] !== null, 'Purchase GraphQL retains its preview relation: ' . $relation);
    }
    $check(GqlRelations::visible($drafts[1]) === null, 'Preview does not authorize other drafts.');
    $activate($otherSiteScope);
    $check(GqlRelations::visible($preview) === null, 'Preview must respect site permission.');
} finally {
    $app->config->general->enableGraphQlCaching = $previousCache;
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
    $app->gql->invalidateCaches();
}
echo "Passed $checks purchased-ticket relation checks on PHP " . PHP_VERSION . ".\n";
