<?php

use craft\helpers\StringHelper;
use craft\models\GqlSchema;
use verbb\base\elements\db\CachedElementQuery;
use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\elements\Session;
use verbb\events\gql\resolvers\SessionResolver;

$appPath = getenv('VERBB_TEST_APP');
$userId = getenv('VERBB_TEST_CP_USER_ID');
$database = getenv('VERBB_TEST_DISPOSABLE_DATABASE');

if (!$appPath || !$userId || !$database) {
    throw new RuntimeException('Set VERBB_TEST_APP, VERBB_TEST_CP_USER_ID and VERBB_TEST_DISPOSABLE_DATABASE to a disposable, populated Craft installation.');
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
$types = Events::$plugin->getEventTypes()->getAllEventTypes();
$check(count($types) >= 2, 'Two event types are required.');
$type = $types[0];
$sites = array_keys($type->getSiteSettings());
$check(count($sites) >= 2, 'The event type must support two sites.');
[$siteId, $otherSiteId] = array_map('intval', array_slice($sites, 0, 2));
$resetCache = static function(): void {
    (new ReflectionProperty(CachedElementQuery::class, '_cache'))->setValue(null, []);
};
$save = static function($element) use ($app): void {
    if (!$app->elements->saveElement($element)) {
        throw new RuntimeException(json_encode($element->getErrors()));
    }
};
$schemaFor = static function(array $permissions = [], ?array $siteIds = null, ?string $typeUid = null) use ($app, $type, $siteId, $otherSiteId): GqlSchema {
    $scope = ['eventsEventTypes.' . ($typeUid ?? $type->uid) . ':read', ...$permissions];
    foreach ($siteIds ?? [$siteId, $otherSiteId] as $id) {
        $scope[] = 'sites.' . $app->sites->getSiteById($id)->uid . ':read';
    }
    return new GqlSchema(['name' => 'Session regression', 'uid' => StringHelper::UUID(), 'isPublic' => true, 'scope' => $scope]);
};
$activate = static function(GqlSchema $schema) use ($app, $resetCache): void {
    $app->gql->flushCaches();
    $app->gql->setActiveSchema($schema);
    $resetCache();
};
$execute = static function(string $query, array $variables = []) use ($app, $resetCache, $check): array {
    $resetCache();
    $result = $app->gql->executeQuery($app->gql->getActiveSchema(), $query, $variables);
    $check(empty($result['errors']), 'GraphQL execution failed: ' . json_encode($result['errors'] ?? []));
    return $result['data'];
};
$queryOwner = static function(int $ownerId, int $site, string $extra = '') use ($execute): array {
    return $execute('{ eventsSessions(eventId:[' . $ownerId . '],siteId:[' . $site . ']' . $extra . ') { id event { id title siteId } } }')['eventsSessions'];
};
$app->user->setIdentity(null);
$transaction = $app->db->beginTransaction();

try {
    $event = new Event(['typeId' => $type->id, 'siteId' => $siteId, 'title' => 'Published event']);
    $save($event);
    $session = new Session([
        'primaryOwner' => $event, 'owner' => $event, 'siteId' => $siteId,
        'startDate' => new DateTime('2030-01-01 10:00 UTC'),
        'endDate' => new DateTime('2030-01-01 11:00 UTC'),
    ]);
    $save($session);
    $resetCache();
    $translated = Event::find()->id($event->id)->siteId($otherSiteId)->one();
    $check($translated !== null, 'The event must propagate to the second site.');
    $translated->title = 'Translated published event';
    $save($translated);
    $drafts = [];
    foreach ([false, true] as $provisional) {
        $draft = $app->drafts->createDraft($event, (int)$userId, null, null, [], $provisional);
        $draft->title = $provisional ? 'Private provisional title' : 'Private named title';
        $save($draft);
        $drafts[] = $draft;
    }
    $revisionId = $app->revisions->createRevision($event, (int)$userId, null, [], true);
    $resetCache();
    $revision = Event::find()->id($revisionId)->revisions(true)->siteId($siteId)->one();
    $check($revision !== null, 'A real event revision is required.');

    $restricted = $schemaFor();
    $activate($restricted);
    foreach ([$siteId, $otherSiteId] as $site) {
        $published = $queryOwner($event->id, $site);
        $check(count($published) === 1 && (int)$published[0]['event']['id'] === $event->id, 'Published sessions must remain readable in both sites.');
        $check($published[0]['event']['title'] === ($site === $siteId ? 'Published event' : 'Translated published event'), 'The event must use the session locale.');
        foreach ($drafts as $draft) {
            $check($queryOwner($draft->id, $site) === [], 'Draft owners must not be readable without draft permission.');
            $data = $execute('{ one: eventsSession(eventId:[' . $draft->id . '],siteId:[' . $site . ']) { id event { title } } count: eventsSessionCount(eventId:[' . $draft->id . '],siteId:[' . $site . ']) }');
            $check($data['one'] === null && $data['count'] === 0, 'Single and count queries must enforce the same owner permissions.');
            foreach ([[$event->id, $draft->id], [$draft->id, $event->id]] as $ids) {
                $data = $execute('query($ids:[QueryArgument]) { mixed: eventsSessions(eventId:$ids,siteId:[' . $site . ']) { event { id title } } count: eventsSessionCount(eventId:$ids,siteId:[' . $site . ']) }', ['ids' => $ids]);
                $check(count($data['mixed']) === 1 && (int)$data['mixed'][0]['event']['id'] === $event->id && $data['count'] === 1, 'Mixed owner IDs must not reintroduce a draft during result hydration.');
            }
            $resetCache();
            $trusted = Session::find()->eventId($draft->id)->siteId($site)->one();
            $check($trusted !== null && $trusted->getEvent()->id === $draft->id, 'Trusted PHP queries must still resolve draft owners without an identity.');
            $check(SessionResolver::resolveEvent($trusted) === null, 'Already-loaded session relationships must also reject hidden draft owners.');
        }
    }

    $activate($schemaFor(['elements.drafts:read']));
    foreach ([$siteId, $otherSiteId] as $site) {
        foreach ($drafts as $draft) {
            $rows = $queryOwner($draft->id, $site);
            $check(count($rows) === 1 && (int)$rows[0]['event']['id'] === $draft->id && (int)$rows[0]['event']['siteId'] === $site, 'Draft-enabled schemas must retain named and provisional owner access in both sites.');
            $data = $execute('{ eventsEvents(id:[' . $draft->id . '],siteId:[' . $site . '],drafts:true,provisionalDrafts:' . ($draft->isProvisionalDraft ? 'true' : 'false') . ') { sessions { event { id } } } }');
            $check((int)($data['eventsEvents'][0]['sessions'][0]['event']['id'] ?? 0) === $draft->id, 'Nested event-session relationships must preserve authorized draft previews.');
        }
    }

    foreach ([$schemaFor([], [], $type->uid), $schemaFor([], [$otherSiteId]), $schemaFor([], null, $types[1]->uid)] as $deniedSchema) {
        $activate($deniedSchema);
        $check($queryOwner($event->id, $siteId) === [], 'A schema must not see an event outside its site/type scope.');
        $check(SessionResolver::resolveEvent($session) === null, 'Nested event resolution must enforce site/type scope independently.');
    }
    $activate($schemaFor([], [$siteId]));
    $data = $execute('{ eventsSessions(eventId:[' . $event->id . '],siteId:["*"]) { event { siteId } } }');
    $check(count($data['eventsSessions']) === 1 && (int)$data['eventsSessions'][0]['event']['siteId'] === $siteId, 'Wildcard site queries must remain within the schema scope.');
    $app->sites->setCurrentSite($app->sites->getSiteById($siteId));
    $data = $execute('{ eventsSessions(eventId:[' . $event->id . ']) { event { id } } }');
    $check(count($data['eventsSessions']) === 1, 'Omitting siteId must retain the normal default-site behavior.');
    $noEvents = $schemaFor();
    $noEvents->scope = array_values(array_filter($noEvents->scope, static fn($scope) => !str_starts_with($scope, 'eventsEventTypes.')));
    $activate($noEvents);
    $check(SessionResolver::resolveEvent($session) === null, 'No event-type scope must deny already-loaded owners.');

    $activate($restricted);
    $check($queryOwner($revisionId, $siteId) === [], 'Revision owners must be hidden without revision permission.');
    $resetCache();
    $revisionSession = Session::find()->eventId($revisionId)->siteId($siteId)->status(null)->revisions(null)->one();
    $check($revisionSession !== null, 'The revision must own a session.');
    $check(SessionResolver::resolveEvent($revisionSession) === null, 'Nested revision owners must be hidden.');
    $activate($schemaFor(['elements.revisions:read']));
    $check(SessionResolver::resolveEvent($revisionSession)?->id === $revisionId, 'Revision permission must preserve nested revision access.');
    $check(count($queryOwner($revisionId, $siteId, ',revisions:null')) === 1, 'Revision-enabled session queries must remain usable.');

    $translated->enabledForSite = false;
    $save($translated);
    $activate($restricted);
    $check($queryOwner($event->id, $otherSiteId) === [], 'An enabled owner in one site must not expose its disabled translation.');
    $check(count($queryOwner($event->id, $siteId)) === 1, 'Disabling a translation must not hide the published primary site.');
    $activate($schemaFor(['elements.inactive:read']));
    $check(count($queryOwner($event->id, $otherSiteId)) === 1, 'Inactive permission must retain disabled translation access.');

    $drafts[0]->enabled = false;
    $save($drafts[0]);
    $activate($schemaFor(['elements.drafts:read']));
    $check($queryOwner($drafts[0]->id, $siteId) === [], 'Draft permission alone must not expose a disabled owner.');
    $activate($schemaFor(['elements.drafts:read', 'elements.inactive:read']));
    $check(count($queryOwner($drafts[0]->id, $siteId)) === 1, 'Inactive permission must preserve disabled draft access.');

    // Craft registers a server-side placeholder only after authorizing its preview token.
    $activate($restricted);
    foreach ($drafts as $index => $draft) {
        $draft->previewing = true;
        $app->elements->setPlaceholderElement($draft);
        $data = $execute('{ eventsEvents(id:[' . $event->id . '],siteId:[' . $siteId . ']) { id sessions { event { id title } } } }');
        $check((int)($data['eventsEvents'][0]['sessions'][0]['event']['id'] ?? 0) === $draft->id, 'A registered preview placeholder must retain its nested event relationship.');
        $check($queryOwner($drafts[1 - $index]->id, $siteId) === [], 'Previewing one draft must not grant access to another draft.');
    }
    $activate($schemaFor([], [$otherSiteId]));
    $previewSession = $drafts[1]->getSessions()->first();
    $check(SessionResolver::resolveEvent($previewSession) === null, 'A preview placeholder must not bypass site scope.');
} finally {
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
}

echo "Passed $checks GraphQL session-owner checks on PHP " . PHP_VERSION . ".\n";
