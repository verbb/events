<?php

use craft\db\Query;
use craft\elements\User;
use craft\events\AuthorizationCheckEvent;
use craft\services\Elements;
use verbb\base\elements\db\CachedElementQuery;
use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\elements\Session;
use verbb\events\elements\TicketType;
use verbb\events\fieldlayoutelements\TicketTypesField;
use verbb\events\helpers\TicketTypePermissions;
use yii\web\ForbiddenHttpException;

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
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_SERVER['REQUEST_URI'] = '/admin/actions/nested-elements/reorder';
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
$type = Events::$plugin->getEventTypes()->getAllEventTypes()[0];
$sites = array_keys($type->getSiteSettings());
$check(count($sites) >= 2, 'Two event sites are required.');
$app->request->setIsCpRequest(true);
$app->user->setIdentity($user);
$permissions = static function(bool $eventEdit = true, bool $ticketEdit = true, ?array $allowedSites = null) use ($app, $user, $type, $sites): void {
    $values = ['accessCp', 'accessPlugin-events', 'events-viewEvents:' . $type->uid, 'events-viewTicketTypes:' . $type->uid];
    if ($eventEdit) {
        $values[] = 'events-editEvents:' . $type->uid;
    }
    if ($ticketEdit) {
        $values[] = 'events-editTicketTypes:' . $type->uid;
    }
    foreach ($allowedSites ?? $sites as $site) {
        $values[] = 'editSite:' . $app->sites->getSiteById($site)->uid;
    }
    $app->userPermissions->saveUserPermissions($user->id, $values);
};
$reset = static function(): void {
    (new ReflectionProperty(CachedElementQuery::class, '_cache'))->setValue(null, []);
};
$save = static function($element) use ($app): void {
    if (!$app->elements->saveElement($element)) {
        throw new RuntimeException(json_encode($element->getErrors()));
    }
};
$fixture = static function(int $site) use ($type, $save, $app, $reset): Event {
    $app->sites->setCurrentSite($site);
    $owner = new Event(['typeId' => $type->id, 'siteId' => $site, 'title' => 'Ticket ordering regression', 'postDate' => new DateTime('-1 day')]);
    $save($owner);
    foreach (['First', 'Second', 'Disabled'] as $name) {
        $save(new TicketType(['primaryOwner' => $owner, 'owner' => $owner, 'siteId' => $site, 'title' => $name, 'price' => 25, 'capacity' => 20, 'enabled' => $name !== 'Disabled']));
    }
    $reset();
    return Event::find()->id($owner->id)->siteId($site)->one();
};
$order = static fn(Event $owner): array => (new Query())->select('elementId')->from('{{%elements_owners}}')->where(['ownerId' => $owner->id])->andWhere(['elementId' => $owner->getTicketTypes(true)->map(static fn($ticket) => $ticket->id)->all()])->orderBy(['sortOrder' => SORT_ASC])->column();
$auth = static fn(Event $owner, string $attribute = 'allTicketTypes'): string => sprintf('reorderNestedElements::%s::%s', $owner->id, $attribute);
$render = static function(Event $owner, bool $static = false): void {
    (new ReflectionMethod(TicketTypesField::class, 'inputHtml'))->invoke(new TicketTypesField(), $owner, $static);
};
$reorder = static function(Event $owner, array $ids, string $attribute = 'allTicketTypes') use ($app, $reset): void {
    $reset();
    $app->controller = null;
    $app->request->setQueryParams([]);
    $app->request->setBodyParams(['ownerElementType' => Event::class, 'ownerId' => $owner->id, 'ownerSiteId' => $owner->siteId, 'attribute' => $attribute, 'elementIds' => $ids, 'offset' => 0, $app->request->csrfParam => $app->request->getCsrfToken()]);
    $app->runAction('nested-elements/reorder');
};
$denied = static function(Event $owner, array $ids, string $attribute = 'allTicketTypes') use ($app, $auth, $reorder, $order, $check): void {
    $before = $order($owner);
    $app->session->authorize($auth($owner, $attribute));
    $rejected = false;
    try {
        $reorder($owner, $ids, $attribute);
    } catch (ForbiddenHttpException) {
        $rejected = true;
    }
    $check($rejected, 'Current authorization must reject the reorder despite an existing session grant.');
    $check($order($owner) === $before, 'Rejected reorders must not change stored positions.');
};
$elementAction = static function(Event $owner, string $action, array $params) use ($app, $reset): array {
    $reset();
    $app->controller = null;
    $app->request->setQueryParams([]);
    $app->request->setBodyParams($params + ['elementId' => $owner->id, 'elementType' => Event::class, 'siteId' => $owner->siteId, $app->request->csrfParam => $app->request->getCsrfToken()]);
    return $app->runAction('elements/' . $action)?->data ?? [];
};
$transaction = $app->db->beginTransaction();
try {
    $app->users->assignUserToGroups($user->id, []);
    foreach (array_slice($sites, 0, 2) as $site) {
        $permissions();
        $owner = $fixture($site);
        $ids = $order($owner);
        foreach ([[false, false], [true, false], [false, true]] as [$eventEdit, $ticketEdit]) {
            $permissions($eventEdit, $ticketEdit);
            foreach ([false, true] as $static) {
                $app->session->deauthorize($auth($owner));
                $render($owner, $static);
                $check(!$app->session->checkAuthorization($auth($owner)), 'Read-only and mixed permissions must not grant sorting.');
            }
            foreach (['allTicketTypes', 'ticketTypes', 'ALLTICKETTYPES', 'TicketTypes'] as $attribute) {
                $denied($owner, [$ids[1]], $attribute);
            }
        }
        $permissions();
        $app->session->deauthorize($auth($owner));
        $render($owner, true);
        $check(!$app->session->checkAuthorization($auth($owner)), 'A static editor must not grant sorting even to an editor.');
        $render($owner);
        $check($app->session->checkAuthorization($auth($owner)), 'An authorized editor must receive sorting authorization.');
        $reorder($owner, [$ids[2]]);
        $check($order($owner) === [$ids[2], $ids[0], $ids[1]], 'Authorized sorting must include disabled ticket types.');
        $permissions(true, false);
        $denied($owner, [$ids[1]]);
        $permissions(true, true, array_values(array_diff($sites, [$site])));
        $check(!$user->can('editSite:' . $app->sites->getSiteById($site)->uid), 'The fixture must not inherit permission to the denied site.');
        $check($app->getIsMultiSite(), 'The fixture must be a multisite installation.');
        $denied($owner, [$ids[1]]);
        $permissions();
        foreach ([$owner->id, $ids[0]] as $vetoId) {
            $veto = static function(AuthorizationCheckEvent $event) use ($vetoId): void {
                if ($event->element->id === $vetoId) {
                    $event->authorized = false;
                }
            };
            $app->elements->on(Elements::EVENT_AUTHORIZE_SAVE, $veto);
            try {
                $check(!TicketTypePermissions::canReorder($owner), 'Owner and shifted-sibling authorization vetoes must be respected.');
                $denied($owner, [$ids[1]]);
            } finally {
                $app->elements->off(Elements::EVENT_AUTHORIZE_SAVE, $veto);
            }
        }
        $permissions(true, false);
        $grant = static function(AuthorizationCheckEvent $event): void {
            if ($event->element instanceof TicketType) {
                $event->authorized = true;
            }
        };
        $app->elements->on(Elements::EVENT_AUTHORIZE_SAVE, $grant);
        try {
            $check(TicketTypePermissions::canReorder($owner), 'A custom ticket authorization grant must remain effective.');
            $reorder($owner, [$ids[1]]);
            $check($order($owner)[0] === $ids[1], 'Custom-authorized sorting must persist.');
        } finally {
            $app->elements->off(Elements::EVENT_AUTHORIZE_SAVE, $grant);
        }
        $permissions();
        $foreign = $fixture($site);
        $session = new Session(['primaryOwner' => $owner, 'owner' => $owner, 'siteId' => $site, 'startDate' => new DateTime('+1 day'), 'endDate' => new DateTime('+1 day +1 hour')]);
        $save($session);
        foreach ([$order($foreign)[0], $session->id] as $invalidId) {
            $denied($owner, [$ids[1], $invalidId]);
        }
        $revisionId = $app->revisions->createRevision($owner, $user->id, null, [], true);
        $revision = Event::find()->id($revisionId)->siteId($site)->revisions(true)->one();
        $check($revision !== null, 'Revision control must exist.');
        $denied($revision, []);
        $app->user->setIdentity(null);
        $app->elements->reorderNestedElements($owner, $owner->getTicketTypes(true), [$ids[0]], 0);
        $check($order($owner)[0] === $ids[0], 'Trusted PHP must retain reordering without a user.');
        $app->user->setIdentity($user);

        foreach ([false, true] as $provisional) {
            $permissions();
            $canonical = $fixture($site);
            $original = $order($canonical);
            $result = $elementAction($canonical, 'save-draft', ['provisional' => $provisional]);
            $check(!isset($result['errors']), 'The event draft must save.');
            $reset();
            $draft = Event::find()->draftOf($canonical->id)->drafts(null)->provisionalDrafts(null)->siteId($site)->one();
            $check($draft !== null, 'The draft must exist.');
            $render($draft);
            $draftIds = $order($draft);
            $reorder($draft, [$draftIds[1]]);
            $check($order($draft)[0] === $draftIds[1], 'Draft sorting must persist.');
            $check($order($canonical) === $original, 'Draft sorting must not change the canonical order.');
            $input = $provisional ? 'allTicketTypes' : 'ticketTypes';
            $result = $elementAction($draft, 'save-draft', ['draftId' => $draft->draftId, 'provisional' => $provisional, $input => '*']);
            $check(!isset($result['errors']), 'Draft autosave must succeed.');
            $reset();
            $draft = Event::find()->id($draft->id)->drafts(null)->provisionalDrafts(null)->siteId($site)->one();
            $result = $elementAction($draft, 'apply-draft', ['draftId' => $draft->draftId, 'provisional' => $provisional, $input => '*']);
            $check(!isset($result['errors']), 'Applying the event draft must succeed.');
            $check($order($canonical)[0] === $original[1], 'Applying the draft must publish the new ticket order.');
        }
    }
} finally {
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
}
echo "Passed $checks ticket-type ordering checks on PHP " . PHP_VERSION . ".\n";
