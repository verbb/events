<?php

use craft\db\Query;
use craft\elements\User;
use craft\events\AuthorizationCheckEvent;
use craft\fields\PlainText;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\StringHelper;
use craft\helpers\Db;
use craft\db\Table;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\services\Elements;
use verbb\base\elements\db\CachedElementQuery;
use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\elements\Session;
use verbb\events\frequencies\Daily;
use verbb\events\models\OccurrenceRange;
use yii\web\ForbiddenHttpException;

$appPath = getenv('VERBB_TEST_APP');
$userId = getenv('VERBB_TEST_CP_USER_ID');
$database = getenv('VERBB_TEST_DISPOSABLE_DATABASE');

if (!$appPath || !$userId || !$database) {
    throw new RuntimeException('Set VERBB_TEST_APP, VERBB_TEST_CP_USER_ID and VERBB_TEST_DISPOSABLE_DATABASE to a disposable, populated Craft test installation.');
}

$_SERVER['SCRIPT_FILENAME'] = $appPath . '/web/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_SERVER['REQUEST_URI'] = '/' . (getenv('VERBB_TEST_CP_TRIGGER') ?: 'admin') . '/actions/elements/save';

require $appPath . '/bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/web.php';

$checks = 0;
$check = static function(bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};
$check($app->db->createCommand('SELECT DATABASE()')->queryScalar() === $database, 'The connected database must match the explicitly named disposable database.');
$user = User::find()->id($userId)->one();
$check($user !== null && !$user->admin && $user->can('accessCp'), 'Use a non-admin CP user.');
$type = Events::$plugin->getEventTypes()->getAllEventTypes()[0];
$sites = array_keys($type->getSiteSettings());
$check(count($sites) >= 2, 'The fixture event type must support at least two sites.');
$siteId = (int)$sites[0];
$otherSiteId = (int)$sites[1];
$resetCache = static function(): void {
    (new ReflectionProperty(CachedElementQuery::class, '_cache'))->setValue(null, []);
};
$load = static function(int $id, ?int $site = null) use ($siteId, $resetCache): Session {
    $resetCache();
    return Session::find()->id($id)->siteId($site ?? $siteId)->status(null)->drafts(null)->provisionalDrafts(null)->one() ?? throw new RuntimeException("Missing session $id.");
};
$timestamp = static fn(int $id): int => (new DateTime((new Query())->select('startDate')->from('{{%events_sessions}}')->where(['id' => $id])->scalar(), new DateTimeZone('UTC')))->getTimestamp();
$save = static function($element) use ($app): void {
    if (!$app->elements->saveElement($element)) {
        throw new RuntimeException(json_encode($element->getErrors()));
    }
};
$owner = static function() use ($type, $siteId, $save): Event {
    $event = new Event(['typeId' => $type->id, 'siteId' => $siteId, 'title' => 'Occurrence regression']);
    $save($event);
    return $event;
};
$session = static function(Event $event, ?string $group, int $day = 1) use ($siteId, $save): Session {
    $session = new Session([
        'primaryOwner' => $event, 'owner' => $event, 'siteId' => $siteId,
        'groupUid' => $group,
        'startDate' => new DateTime("2030-01-$day 10:00:00 UTC"),
        'endDate' => new DateTime("2030-01-$day 11:00:00 UTC"),
    ]);
    $save($session);
    return $session;
};
$series = static function() use ($owner, $session): array {
    $event = $owner();
    $group = StringHelper::UUID();
    return [$event, $session($event, $group), $session($event, $group, 2), $session($event, $group, 3)];
};
$request = static function(Event|Session $source, string $action, array $params) use ($app, $resetCache): array {
    $resetCache();
    $app->controller = null;
    $app->request->setQueryParams([]);
    $app->request->setBodyParams($params + [
        $app->request->csrfParam => $app->request->getCsrfToken(),
        'elementId' => $source->id, 'elementType' => $source::class,
        'siteId' => $source->siteId, 'ownerId' => $source instanceof Session ? $source->getOwnerId() : null,
    ]);
    return $app->runAction('elements/' . $action)?->data ?? [];
};
$rangeParams = static fn(string $type): array => ['occurrenceRange' => ['type' => $type, 'startDateOffset' => 3600, 'endDateOffset' => 3600]];

$transaction = $app->db->beginTransaction();
try {
    $permissions = $app->userPermissions->getPermissionsByUserId($user->id);
    $app->userPermissions->saveUserPermissions($user->id, array_merge($permissions, [
        'accessCp', 'accessPlugin-events', 'events-viewEvents:' . $type->uid,
        'events-editEvents:' . $type->uid, 'events-createEvents:' . $type->uid,
        'events-viewSessions:' . $type->uid, 'events-editSessions:' . $type->uid,
        'events-createSessions:' . $type->uid,
    ], array_map(static fn($site) => 'editSite:' . $app->sites->getSiteById($site)->uid, $sites)));
    $app->user->setIdentity($user);

    [$event, $first, $second, $third] = $series();
    $unrelated = $session($owner(), $first->groupUid, 2);
    $standalone = $session($event, null);
    $original = $timestamp($unrelated->id);
    foreach (['all', 'future', 'custom'] as $rangeType) {
        $params = $rangeParams($rangeType);
        $params['occurrenceRange'] += ['startDate' => '2020-01-01', 'endDate' => '2040-01-01'];
        $result = $request($standalone, 'save', $params);
        $check(isset($result['errors']['occurrenceRange.type']), "Reject $rangeType on a non-recurring session.");
        $check($timestamp($unrelated->id) === $original, 'Rejected requests must not change another event.');
    }

    foreach (['all', 'future', 'custom'] as $rangeType) {
        [$event, $first, $second, $third] = $series();
        $sameGroupOtherEvent = $session($owner(), $first->groupUid, 2);
        $otherBefore = $timestamp($sameGroupOtherEvent->id);
        $before = array_map($timestamp, [$first->id, $second->id, $third->id]);
        $params = $rangeParams($rangeType);
        $params['occurrenceRange'] += ['startDate' => '2030-01-03', 'endDate' => '2030-01-04'];
        $result = $request($second, 'save', $params);
        $check(!isset($result['errors']), "Legitimate $rangeType request must save: " . json_encode($result['errors'] ?? []));
        $check($timestamp($first->id) === $before[0] + ($rangeType === 'all' ? 3600 : 0), "$rangeType first occurrence.");
        $check($timestamp($third->id) === $before[2] + 3600, "$rangeType later occurrence.");
        $check($timestamp($unrelated->id) === $original, 'Identical group IDs in other events must remain isolated.');
        $check($timestamp($sameGroupOtherEvent->id) === $otherBefore, 'A copied group UID must not join separate events.');
        $check($timestamp($second->id) === $before[1], 'Offset-only input must not shift the starting session outside a custom range.');
    }

    [$event, $first, $second, $third] = $series();
    $before = $timestamp($second->id);
    $params = $rangeParams('custom');
    $params['occurrenceRange'] += ['startDate' => '2030-01-02', 'endDate' => '2030-01-03'];
    $result = $request($second, 'save', $params);
    $check(!isset($result['errors']) && $timestamp($second->id) === $before + 3600, 'Custom ranges must update the source exactly once when it falls inside the range.');
    $params['occurrenceRange']['endDate'] = '';
    $result = $request($second, 'save', $params);
    $check(isset($result['errors']), 'Incomplete custom date ranges must be rejected.');

    $event = $owner();
    $recurring = new Session([
        'primaryOwner' => $event, 'owner' => $event, 'siteId' => $siteId,
        'startDate' => new DateTime('2030-02-01 10:00:00 UTC'),
        'endDate' => new DateTime('2030-02-01 11:00:00 UTC'),
        'frequency' => new Daily(['repeatEnd' => ['type' => 'after', 'count' => 3]]),
    ]);
    $recurring->setIsFresh();
    $save($recurring);
    $recurring = $load($recurring->id);
    $targets = (new OccurrenceRange(['type' => 'all']))->getSessions($recurring);
    $check($recurring->groupUid !== null && count($targets) === 2, 'Normal recurrence generation must retain all three occurrences.');
    $before = $timestamp($targets[0]->id);
    $result = $request($recurring, 'save', $rangeParams('all'));
    $check(!isset($result['errors']) && $timestamp($targets[0]->id) === $before + 3600, 'Generated recurring sessions must support bulk edits.');

    [$event, $first, $second, $third] = $series();
    $before = $timestamp($third->id);
    $deny = static function(AuthorizationCheckEvent $event) use ($third): void {
        $event->authorized = $event->element->id === $third->id ? false : null;
    };
    $app->elements->on(Elements::EVENT_AUTHORIZE_SAVE, $deny);
    try {
        $rejected = false;
        try {
            $request($first, 'save', $rangeParams('all'));
        } catch (ForbiddenHttpException) {
            $rejected = true;
        }
        $check($rejected, 'Target-specific authorization hooks must reject the bulk request.');
        $check($timestamp($third->id) === $before && $timestamp($second->id) === $before - 86400, 'Denied target must roll back all siblings.');
    } finally {
        $app->elements->off(Elements::EVENT_AUTHORIZE_SAVE, $deny);
    }

    $app->user->setIdentity(null);
    $source = $load($first->id);
    $source->setScenario(Session::SCENARIO_LIVE);
    $source->setAttributesFromRequest($rangeParams('all'));
    $rejected = false;
    try {
        $save($source);
    } catch (ForbiddenHttpException) {
        $rejected = true;
    }
    $check($rejected, 'Request-created ranges must reject absent user identities.');
    $source = $load($first->id);
    $source->occurrenceRange = new OccurrenceRange(['type' => 'all', 'startDateOffset' => 1800, 'endDateOffset' => 1800]);
    $save($source);
    $check($timestamp($third->id) === $before + 1800, 'Trusted PHP must work without a logged-in user during a CP request.');
    $app->request->setIsCpRequest(false);
    $source = $load($first->id);
    $source->occurrenceRange = new OccurrenceRange(['type' => 'all', 'startDateOffset' => 1800, 'endDateOffset' => 1800]);
    $save($source);
    $check($timestamp($third->id) === $before + 3600, 'Trusted background-style saves must work without an identity.');
    $app->request->setIsCpRequest(true);
    $app->user->setIdentity($user);

    foreach ([false, true] as $provisional) {
        [$event, $first, $second, $third] = $series();
        $before = $timestamp($second->id);
        // Named session drafts are created by trusted integrations; the CP creates provisional drafts.
        $draftSource = $provisional ? $first : $app->drafts->createDraft($first, $user->id);
        $result = $request($draftSource, 'save-draft', $rangeParams('all') + ['provisional' => $provisional, 'draftId' => $draftSource->draftId]);
        $check(!isset($result['errors']), 'Session draft must save: ' . json_encode($result['errors'] ?? []));
        $resetCache();
        $draft = Session::find()->draftOf($first->id)->drafts(null)->provisionalDrafts(null)->siteId($siteId)->status(null)->one();
        $check($draft !== null && $draft->getIsDraft(), 'Session draft must exist.');
        $check($timestamp($second->id) === $before, 'Saving a session draft must leave published siblings unchanged.');
        $result = $request($draft, 'apply-draft', $rangeParams('all') + ['draftId' => $draft->draftId, 'provisional' => $provisional]);
        $check(!isset($result['errors']), 'Session draft must apply: ' . json_encode($result['errors'] ?? []));
        $check($timestamp($second->id) === $before + 3600, 'Applying a session draft must update siblings exactly once.');
    }

    [$event, $first, $second, $third] = $series();
    $before = $timestamp($second->id);
    $eventDraft = $app->drafts->createDraft($event, $user->id);
    // Exercise the derivative-owned state through Craft's element API.
    $sessionDraft = $app->elements->duplicateElement($first, ['canonicalId' => $first->id, 'primaryOwner' => $eventDraft, 'owner' => $eventDraft]);
    Db::delete(Table::ELEMENTS_OWNERS, ['elementId' => $first->id, 'ownerId' => $eventDraft->id]);
    $sessionDraft->setScenario(Session::SCENARIO_LIVE);
    $sessionDraft->setAttributesFromRequest($rangeParams('all'));
    $save($sessionDraft);
    $check($timestamp($second->id) === $before, 'Event draft edits must leave canonical siblings unchanged.');
    $resetCache();
    $draftSiblings = Session::find()->ownerId($eventDraft->id)->groupUid($first->groupUid)->siteId($siteId)->drafts(null)->provisionalDrafts(null)->status(null)->all();
    $check(count($draftSiblings) === 3, 'Event draft must retain its full recurring series.');
    foreach ($draftSiblings as $sibling) {
        $check($sibling->getPrimaryOwnerId() === $eventDraft->id, 'Each edited event-draft session must have an isolated record.');
        if ($sibling->getCanonicalId() === $second->id) {
            $check($timestamp($sibling->id) === $before + 3600, 'Event draft sibling must receive the range update.');
        }
    }
    $resetCache();
    $eventDraft = Event::find()->id($eventDraft->id)->drafts(null)->siteId($siteId)->status(null)->one();
    $eventDraft->setDirtyAttributes(['allSessions']);
    $save($eventDraft);
    $app->drafts->applyDraft($eventDraft);
    $check($timestamp($second->id) === $before + 3600, 'Applying the event draft must publish the sibling change once.');

    $field = null;
    foreach ($app->fields->getAllFields() as $candidate) {
        if ($candidate instanceof PlainText) {
            $field = $candidate;
            break;
        }
    }
    $check($field !== null, 'A Plain Text field is required for the multisite fixture.');
    $field->translationMethod = 'site';
    $layout = new FieldLayout(['type' => Session::class]);
    $layout->setTabs([new FieldLayoutTab(['layout' => $layout, 'name' => 'Content', 'elements' => [new CustomField($field)]])]);
    $check($app->fields->saveLayout($layout), 'Save the fixture field layout.');
    $type->sessionFieldLayoutId = $layout->id;
    $type->getBehavior('sessionFieldLayout')->setFieldLayout($layout);
    $app->db->createCommand()->update('{{%events_event_types}}', ['sessionFieldLayoutId' => $layout->id], ['id' => $type->id])->execute();
    [$event, $first, $second, $third] = $series();
    foreach ([$first, $second, $third] as $fixture) {
        foreach ([$siteId, $otherSiteId] as $site) {
            $localized = $load($fixture->id, $site);
            $localized->setFieldValue($field->handle, "Site $site");
            $save($localized);
        }
    }
    $source = $load($first->id, $otherSiteId);
    $app->sites->setCurrentSite($siteId);
    $before = $timestamp($second->id);
    $result = $request($source, 'save', $rangeParams('all') + ['fields' => [$field->handle => 'Updated translation']]);
    $check(!isset($result['errors']), 'Multisite recurring update must save.');
    $check($load($second->id, $otherSiteId)->getFieldValue($field->handle) === 'Updated translation', 'Bulk field changes must use the source session site.');
    $check($load($second->id, $siteId)->getFieldValue($field->handle) === "Site $siteId", 'Bulk field changes must preserve other translations.');
    $check($load($second->id, $siteId)->startDate->getTimestamp() === $before + 3600, 'Shared session dates must remain synchronized between sites.');

    $source = $load($first->id);
    $before = $timestamp($second->id);
    $range = new OccurrenceRange(['type' => 'all', 'startDateOffset' => 1800, 'endDateOffset' => 1800]);
    $range->updateSessions($source);
    $range->updateSessions($source);
    $check($timestamp($second->id) === $before + 3600, 'Repeated trusted operations in one process must select current session dates.');

    foreach ([false, true] as $provisional) {
        foreach ([$siteId, $otherSiteId] as $editingSite) {
            foreach (['all', 'future', 'custom'] as $rangeType) {
                [$event, $first, $second, $third] = $series();
                foreach ([$first, $second, $third] as $fixture) {
                    foreach ([$siteId, $otherSiteId] as $site) {
                        $localized = $load($fixture->id, $site);
                        $localized->setFieldValue($field->handle, "Original $site");
                        $save($localized);
                    }
                }
                $before = $timestamp($second->id);
                $resetCache();
                $event = Event::find()->id($event->id)->siteId($editingSite)->one();
                $result = $request($event, 'save-draft', ['provisional' => $provisional]);
                $check(!isset($result['errors']), 'CP event draft must save.');
                $resetCache();
                $eventDraft = Event::find()->draftOf($event->id)->drafts(null)->provisionalDrafts(null)->siteId($editingSite)->one();
                $check($eventDraft !== null, 'CP event draft must exist.');
                $check(count(Session::find()->owner($eventDraft)->all()) === 3, 'Trusted owner-model queries must preserve draft context and site.');
                $check(Session::find()->owner($eventDraft)->one()->siteId === $editingSite, 'Trusted owner-model queries must use the owner site.');
                $check(Session::find()->ownerId($eventDraft->id)->allowOwnerDrafts(false)->one() === null, 'Explicit owner-draft exclusion must be respected.');
                $shared = Session::find()->id($first->id)->ownerId($eventDraft->id)->siteId($editingSite)->one();
                $check($shared !== null, 'Draft must initially share the canonical session.');
                $result = $request($shared, 'save-draft', ['provisional' => true]);
                $check(!isset($result['errors']), 'CP nested session provisional draft must save.');
                $resetCache();
                $childDraft = Session::find()->draftOf($first->id)->ownerId($eventDraft->id)->drafts(null)->provisionalDrafts(null)->siteId($editingSite)->one();
                $check($childDraft !== null, 'Nested session draft must retain its event-draft owner context.');
                $params = $rangeParams($rangeType);
                $params['occurrenceRange'] += ['startDate' => '2030-01-02', 'endDate' => '2030-01-04'];
                $result = $request($childDraft, 'save-nested-element-for-derivative', $params + [
                    'draftId' => $childDraft->draftId, 'provisional' => true, 'newOwnerId' => $eventDraft->id,
                    'fields' => [$field->handle => 'Draft translation'],
                ]);
                $check(!isset($result['errors']), 'CP must save the session into its event draft.');
                $check($timestamp($second->id) === $before, 'CP draft save must not publish recurring sibling dates.');
                $check($load($second->id, $editingSite)->getFieldValue($field->handle) === "Original $editingSite", 'CP draft save must not publish sibling content.');
                $resetCache();
                $updatedSibling = Session::find()->ownerId($eventDraft->id)->andWhere(['elements.canonicalId' => $second->id])->siteId($editingSite)->one();
                $check($updatedSibling !== null && $timestamp($updatedSibling->id) === $before + 3600, 'CP nested save must update the isolated recurring sibling; actual offset ' . ($updatedSibling ? $timestamp($updatedSibling->id) - $before : 'missing'));
                $inputAttribute = $provisional ? 'allSessions' : 'sessions';
                $result = $request($eventDraft, 'save-draft', ['draftId' => $eventDraft->draftId, 'provisional' => $provisional, $inputAttribute => '*']);
                $check(!isset($result['errors']), 'CP event autosave must record nested session changes.');
                $resetCache();
                $eventDraft = Event::find()->id($eventDraft->id)->drafts(null)->provisionalDrafts(null)->siteId($editingSite)->one();
                $check($eventDraft->isAttributeModified('allSessions'), 'CP autosave must persist the nested manager modification marker.');
                $result = $request($eventDraft, 'apply-draft', ['draftId' => $eventDraft->draftId, 'provisional' => $provisional, $inputAttribute => '*']);
                $check(!isset($result['errors']), 'CP event draft must apply.');
                $check($timestamp($second->id) === $before + 3600, 'CP apply must publish each recurring sibling once; actual offset ' . ($timestamp($second->id) - $before));
                $check($load($second->id, $editingSite)->getFieldValue($field->handle) === 'Draft translation', 'CP apply must publish translated sibling content.');
                $untouchedSite = $editingSite === $siteId ? $otherSiteId : $siteId;
                $check($load($second->id, $untouchedSite)->getFieldValue($field->handle) === "Original $untouchedSite", 'CP apply must retain translations in other sites.');
                $resetCache();
                $check(Session::find()->ownerId($event->id)->siteId($editingSite)->count() === 3, 'CP apply must retain exactly three canonical occurrences.');
            }
        }
    }

    $resetCache();
    $check(count(Session::find()->owner($event->id)->siteId($editingSite)->all()) === 3, 'Trusted numeric owner queries must remain supported.');
    $check(count(Session::find()->primaryOwner([$event->id])->siteId($editingSite)->all()) === 3, 'Trusted primary-owner ID arrays must remain supported.');
    $check(Session::find()->owner($event)->fieldId(null)->one() !== null, 'Craft nested queries must accept the absence of a containing field.');
    $check(Session::find()->owner($event)->fieldId(123)->one() === null, 'Sessions must never match a containing custom field.');
    $otherEvent = $owner();
    $otherSession = $session($otherEvent, null);
    $check(Session::find()->owner($event)->eventId($otherEvent->id)->one()->getOwnerId() === $otherEvent->id, 'The eventId alias must clear a previous owner model.');
    $check(Session::find()->owner($event)->event($otherEvent)->one()->getOwnerId() === $otherEvent->id, 'The event alias must clear a previous owner model.');

    echo "Passed $checks occurrence range regression checks on PHP " . PHP_VERSION . ".\n";
} finally {
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
}
