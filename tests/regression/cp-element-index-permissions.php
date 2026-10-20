<?php

use verbb\base\elements\db\CachedElementQuery;
use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\Session;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;

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
$_SERVER['REQUEST_URI'] = '/admin/actions/element-indexes/get-elements';
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
$run = static function(string $class, array $criteria, string $action = 'export', array $params = []) use ($app): mixed {
    (new ReflectionProperty($app->elementSources, 'sources'))->setValue($app->elementSources, []);
    if (($params['source'] ?? null) === 'custom:fixture') {
        $source = [
            'key' => 'custom:fixture', 'type' => 'custom', 'label' => 'Fixture source',
            'condition' => $class::createCondition()->getConfig(),
        ];
        (new ReflectionProperty($app->elementSources, 'sources'))->setValue($app->elementSources, [$class => ['index' => [$source]]]);
    }
    $app->controller = null;
    $app->request->setQueryParams([]);
    $app->request->setBodyParams($params + [
        'elementType' => $class, 'source' => '*', 'context' => 'index',
        'baseCriteria' => $criteria, 'criteria' => ['status' => null],
        'viewState' => ['mode' => 'table', 'static' => true, 'tableColumns' => ['title']],
        'format' => 'json', $app->request->csrfParam => $app->request->getCsrfToken(),
    ]);
    $response = $app->runAction('element-indexes/' . $action);
    return is_array($response->data) ? $response->data : iterator_to_array($response->data);
};
$transaction = $app->db->beginTransaction();
try {
    $purchases = [];
    $events = [];
    foreach ($types as $index => $type) {
        $check(isset($type->getSiteSettings()[$siteId]), 'Both event types must support the fixture site.');
        $event = new Event(['title' => 'CP permission fixture', 'typeId' => $type->id, 'siteId' => $siteId, 'postDate' => new DateTime('-1 day')]);
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

    $user = \craft\elements\User::find()->id((int)getenv('VERBB_TEST_CP_USER_ID'))->one();
    $check($user !== null && !$user->admin, 'A non-admin fixture user is required.');
    $app->users->assignUserToGroups($user->id, []);
    $app->request->setIsCpRequest(true);
    $app->user->setIdentity($user);
    $events[1]->enabled = false;
    $save($events[1]);
    foreach (['none', 'one', 'both'] as $mode) {
        $grants = ['accessCp', 'editSite:'.$app->sites->getSiteById($siteId)->uid];
        foreach ($types as $i=>$type) {
            if ($mode === 'both' || ($mode === 'one' && $i === 0)) {
                $grants[] = 'events-viewEvents:'.$type->uid;
                $grants[] = 'events-viewSessions:'.$type->uid;
                $grants[] = 'events-viewTicketTypes:'.$type->uid;
            }
        }
        $app->userPermissions->saveUserPermissions($user->id, $grants);
        $check(!$user->can('events-viewEvents:'.$types[1]->uid) || $mode === 'both', 'Denied type has no inherited access.');
        foreach ([Session::class=>'sessionId', TicketType::class=>'ticketTypeId', Ticket::class=>'ticketId', Event::class=>'eventId'] as $class=>$attribute) {
            $fixtureIds = array_map(static fn($purchase)=>$purchase->$attribute, $purchases);
            $criteria = ['id' => $fixtureIds, 'siteId' => $siteId, 'status' => null];
            $trusted = $class::find()->id($fixtureIds)->siteId($siteId)->status(null);
            $check(count($trusted->all()) === 2 && (int)$trusted->count() === 2, 'Trusted PHP on a CP request remains unrestricted and primes the query cache.');
            foreach (['get-elements', 'get-more-elements', 'export', 'count-elements'] as $action) {
                $data = $run($class, $criteria, $action);
                $expected = $mode === 'both' ? $fixtureIds : ($mode === 'one' ? [$fixtureIds[0]] : []);
                if ($action === 'count-elements') {
                    $check((int)$data['total'] === count($expected) && (int)$data['unfilteredTotal'] === count($expected), 'Both filtered and unfiltered counts enforce permissions.');
                    continue;
                }
                if ($action === 'export') {
                    $returned = array_map('intval', array_column($data, 'id'));
                } else {
                    $html = $data['html'] ?? '';
                    $returned = array_values(array_filter($fixtureIds, static fn($id)=>str_contains($html, 'data-id="'.$id.'"')));
                }
                sort($returned);
                sort($expected);
                $check($returned === $expected, "$mode $class $action must return exactly the permitted fixtures.");
            }
            $check(count($trusted->all()) === 2, 'Restricted results must not contaminate trusted query caches.');
        }
    }
    $siteGrant = 'editSite:' . $app->sites->getSiteById($siteId)->uid;
    $classes = [Event::class => 'eventId', Session::class => 'sessionId', TicketType::class => 'ticketTypeId', Ticket::class => 'ticketId'];
    foreach (['events-viewEvents', 'events-viewSessions', 'events-viewTicketTypes'] as $permission) {
        $app->userPermissions->saveUserPermissions($user->id, ['accessCp', $siteGrant, 'events-viewEvents:' . $types[0]->uid, $permission . ':' . $types[0]->uid]);
        foreach ($classes as $class => $attribute) {
            $expected = match ($class) {
                Session::class => $permission === 'events-viewSessions',
                TicketType::class => $permission === 'events-viewTicketTypes',
                default => true,
            };
            $rows = $run($class, ['id' => $purchases[0]->$attribute, 'siteId' => $siteId, 'status' => null]);
            $check(count($rows) === (int)$expected, "$permission $class: child viewing permissions remain independent; returned " . count($rows));
        }
    }

    $allTypeGrants = ['accessCp'];
    foreach ($types as $type) {
        foreach (['events-viewEvents', 'events-viewSessions', 'events-viewTicketTypes'] as $permission) {
            $allTypeGrants[] = $permission . ':' . $type->uid;
        }
    }
    $app->userPermissions->saveUserPermissions($user->id, [
        'accessCp', $siteGrant, 'events-viewEvents:' . $types[0]->uid,
        'events-viewSessions:' . $types[0]->uid, 'events-viewTicketTypes:' . $types[0]->uid,
    ]);
    foreach ($classes as $class => $attribute) {
        $criteria = ['id' => [$purchases[0]->$attribute, $purchases[1]->$attribute], 'siteId' => $siteId, 'status' => null];
        foreach (['*', 'custom:fixture'] as $source) {
            $rows = $run($class, $criteria, 'export', ['source' => $source, 'criteria' => ['id' => $purchases[1]->$attribute, 'status' => null]]);
            $check($rows === [], 'User criteria and custom sources cannot widen the permission scope.');
            $counts = $run($class, $criteria, 'count-elements', ['source' => $source, 'criteria' => ['id' => $purchases[1]->$attribute, 'status' => null]]);
            $check((int)$counts['total'] === 0 && (int)$counts['unfilteredTotal'] === 1, 'Custom source filtered and unfiltered counts share the same boundary.');
        }
        $rejected = false;
        try {
            $run($class, $criteria, 'element-table-html', ['id' => $purchases[1]->$attribute]);
        } catch (\yii\web\BadRequestHttpException) {
            $rejected = true;
        }
        $check($rejected, 'Table refresh clones cannot return a denied row.');
        $data = $run($class, $criteria, 'element-table-html', ['id' => $purchases[0]->$attribute]);
        $check(isset($data['attributeHtml']), 'Table refresh clones retain allowed rows.');
    }
    $otherSiteId = (int)array_keys($types[0]->getSiteSettings())[1];
    $otherSiteGrant = 'editSite:' . $app->sites->getSiteById($otherSiteId)->uid;
    foreach ([$siteId, $otherSiteId] as $site) {
        $app->userPermissions->saveUserPermissions($user->id, [...$allTypeGrants, $site === $siteId ? $siteGrant : $otherSiteGrant]);
        foreach ($classes as $class => $attribute) {
            $criteria = ['id' => $purchases[0]->$attribute, 'siteId' => $site, 'status' => null];
            $rows = $run($class, $criteria);
            $check(count($rows) === 1, 'Authorized multisite selectors remain usable, including nonlocalized tickets.');
            $counts = $run($class, $criteria, 'count-elements', ['context' => 'modal']);
            $check((int)$counts['total'] === 1, 'Modal selector counts preserve authorized multisite access.');
            if ($class !== Ticket::class) {
                $criteria['siteId'] = $site === $siteId ? $otherSiteId : $siteId;
                $check($run($class, $criteria) === [], 'Localized content requires the selected site permission.');
            }
        }
    }
    $app->userPermissions->saveUserPermissions($user->id, [...$allTypeGrants, $siteGrant, $otherSiteGrant]);
    foreach ([false, true] as $provisional) {
        $app->request->setIsCpRequest(false);
        $draft = $app->drafts->createDraft($events[0], $user->id, null, null, [], $provisional);
        $app->request->setIsCpRequest(true);
        foreach ([$siteId, $otherSiteId] as $site) {
            foreach ([Session::class, TicketType::class] as $class) {
                $criteria = ['ownerId' => $draft->id, 'siteId' => $site, 'status' => null, 'allowOwnerDrafts' => true];
                if ($class === TicketType::class) {
                    unset($criteria['allowOwnerDrafts']);
                }
                $rows = $run($class, $criteria);
                $check(count($rows) === 1 && (int)$rows[0]['ownerId'] === $draft->id, 'Nested named/provisional draft selectors retain their effective owner in each site.');
                $counts = $run($class, $criteria, 'count-elements', ['context' => 'modal']);
                $check((int)$counts['total'] === 1, 'Nested draft modal counts remain usable.');
            }
        }
    }

    foreach ($classes as $class => $attribute) {
        $fixtureIds = array_map(static fn($purchase) => $purchase->$attribute, $purchases);
        $criteria = ['id' => $fixtureIds, 'siteId' => $siteId, 'status' => null];
        $user->admin = true;
        $check(count($run($class, $criteria)) === 2, 'Administrators retain both event types.');
        $user->admin = false;
        $app->userPermissions->saveUserPermissions($user->id, ['accessCp', $siteGrant]);
        $check($run($class, $criteria) === [], 'Revoking permission cannot reuse an earlier unrestricted cached result.');
    }
    $app->userPermissions->saveUserPermissions($user->id, ['accessCp', 'editSite:'.$app->sites->getSiteById($siteId)->uid]);
    $app->controller = null;
    $app->request->setBodyParams(['elementType'=>PurchasedTicket::class, 'source'=>'*', 'context'=>'index', 'viewState'=>['mode'=>'table','static'=>true], $app->request->csrfParam=>$app->request->getCsrfToken()]);
    $denied = false;
    try {
        $app->runAction('element-indexes/get-elements');
    } catch (\yii\web\ForbiddenHttpException) {
        $denied = true;
    }
    $check($denied, 'Existing purchased-ticket permission gate is an effective negative control.');
    echo json_encode(['php'=>PHP_VERSION, 'checks'=>$checks], JSON_PRETTY_PRINT), "\n";
} finally {
    if ($transaction->isActive) {
        $transaction->rollBack();
    }
}
