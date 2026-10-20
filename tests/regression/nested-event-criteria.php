<?php

use craft\controllers\ElementIndexesController;
use craft\controllers\ElementSearchController;
use craft\elements\User;
use verbb\base\elements\db\CachedElementQuery;
use verbb\events\elements\Event;
use verbb\events\elements\Session;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;
use verbb\events\elements\db\TicketQuery;
use yii\base\ActionEvent;
use yii\base\Event as YiiEvent;
use yii\web\BadRequestHttpException;

$appPath = getenv('VERBB_TEST_APP');
$userId = getenv('VERBB_TEST_CP_USER_ID');

if (!$appPath || !$userId) {
    throw new RuntimeException('Set VERBB_TEST_APP and VERBB_TEST_CP_USER_ID to a populated Craft test app and an active non-admin CP user.');
}

$_SERVER['SCRIPT_FILENAME'] = $appPath . '/web/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_SERVER['REQUEST_URI'] = '/' . (getenv('VERBB_TEST_CP_TRIGGER') ?: 'admin') . '/actions/element-indexes/count-elements';

require $appPath . '/bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/web.php';

$checks = 0;
$check = static function(bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }

    $checks++;
};

$user = User::find()->id($userId)->one();
$check($user !== null && !$user->admin && $user->can('accessCp'), 'The fixture user must have CP access without administrator privileges.');
$app->getUser()->setIdentity($user);
$check($app->request->getIsCpRequest(), 'The regression must run as a CP request.');
$token = $app->request->getCsrfToken();
$siteId = $app->sites->getPrimarySite()->id;

$resetQueryCache = static function(): void {
    // Each simulated HTTP request needs fresh request-local element query caches.
    foreach ([CachedElementQuery::class, TicketQuery::class] as $class) {
        (new ReflectionProperty($class, '_cache'))->setValue(null, []);
    }
};

$request = static function(string $type, string $input, array $nested, array $queryParams = [], ?string $bodyType = null) use ($app, $token, $siteId, $resetQueryCache): int {
    $resetQueryCache();
    $isSearch = $input === 'search';
    $app->request->setQueryParams($queryParams);
    $app->request->setBodyParams([
        $app->request->csrfParam => $token,
        'elementType' => $bodyType ?? $type,
        'context' => 'modal',
        'source' => '*',
        'siteId' => $siteId,
        'search' => '',
        $isSearch ? 'criteria' : $input => [
            'status' => null,
            'siteId' => $siteId,
            'hasEvent' => $nested,
        ],
    ]);

    $response = $app->runAction($isSearch ? 'element-search/search' : 'element-indexes/count-elements');

    return $isSearch ? count($response->data['elements']) : (int)$response->data['total'];
};

$payloads = [
    'where string' => ['where' => '1=0'],
    'where array' => ['where' => ['and', '1=0']],
    'having' => ['having' => '1=0'],
    'join' => ['join' => [['INNER JOIN', '(SELECT 1 AS id) events_probe', '1=0']]],
    'union' => ['union' => [['query' => 'SELECT events_probe_missing_column', 'all' => true]]],
    'orderBy' => ['orderBy' => 'events_probe_missing_function()'],
    'search config' => ['search' => ['query' => 'events_probe_no_match']],
    'component config' => ['on beforePrepare' => 'events_probe_missing_handler', 'as eventsProbe' => ['class' => 'events_probe_missing_behavior']],
];

foreach ([Ticket::class => 'tickets', TicketType::class => 'ticketTypes', Session::class => 'sessions'] as $type => $method) {
    $baseline = ['status' => null, 'siteId' => $siteId];
    $eventIds = Event::find()->status(null)->siteId($siteId)->ids();

    foreach (['criteria', 'baseCriteria', 'search'] as $input) {
        $total = $request($type, $input, $baseline);
        $check($total > 0, "$type needs related event fixtures for $input.");
        $check($request($type, $input, $baseline + ['id' => $eventIds]) === $total, "$type/$input must preserve ordinary event ID filters.");
        $check($request($type, $input, $baseline + ['id' => 0]) === 0, "$type/$input must apply ordinary event ID filters.");

        foreach ($payloads as $label => $payload) {
            $check($request($type, $input, $baseline + $payload) === $total, "$type/$input accepted nested $label.");
        }

        if ($input === 'search') {
            $check($request('\\' . $type, $input, $baseline + ['where' => '1=0'], ['elementType' => User::class]) === $total, "$type search must clean the body element type despite a conflicting URL parameter.");
        } else {
            $check($request($type, $input, $baseline + ['where' => '1=0'], ['elementType' => '\\' . $type], User::class) === $total, "$type/$input must clean the effective URL element type.");
        }
    }

    $app->request->setQueryParams([]);

    $app->controller = null;
    $resetQueryCache();
    $trustedTotal = (int)$type::find()->status(null)->siteId($siteId)->hasEvent($baseline)->count();
    $check($trustedTotal > 0, "$type needs a positive trusted-query control.");
    $check((int)$type::find()->status(null)->siteId($siteId)->hasEvent($baseline + ['where' => '1=1'])->count() === $trustedTotal, "$type must preserve trusted PHP SQL criteria.");
    $check((int)$type::find()->status(null)->siteId($siteId)->hasEvent($baseline + ['where' => '1=0'])->count() === 0, "$type must apply trusted PHP SQL criteria.");

    foreach (['1=1' => $trustedTotal, '1=0' => 0] as $where => $expected) {
        $rendered = $app->view->renderString('{{ craft.events.' . $method . '({status: null, siteId: siteId, hasEvent: {status: null, siteId: siteId, where: sql}}).count() }}', ['siteId' => $siteId, 'sql' => $where]);
        $check((int)$rendered === $expected, "$type must preserve trusted Twig SQL criteria.");
    }

    foreach ([ElementIndexesController::class, ElementSearchController::class] as $controllerClass) {
        $app->controller = new $controllerClass('events-regression', $app);

        foreach (['1=1' => $trustedTotal, '1=0' => 0] as $where => $expected) {
            $resetQueryCache();
            $check((int)$type::find()->status(null)->siteId($siteId)->hasEvent($baseline + ['where' => $where])->count() === $expected, "$type must preserve trusted PHP arrays in $controllerClass.");
            $resetQueryCache();
            $rendered = $app->view->renderString('{{ craft.events.' . $method . '({status: null, siteId: siteId, hasEvent: {status: null, siteId: siteId, where: sql}}).count() }}', ['siteId' => $siteId, 'sql' => $where]);
            $check((int)$rendered === $expected, "$type must preserve trusted Twig arrays in $controllerClass.");
            $resetQueryCache();
            $eventQuery = Event::find()->status(null)->siteId($siteId)->where($where);
            $check((int)$type::find()->status(null)->siteId($siteId)->hasEvent($eventQuery)->count() === $expected, "$type must preserve trusted EventQuery objects in $controllerClass.");
        }

        $app->controller = null;
        $callback = static function(ActionEvent $event) use ($app, $check, $type, $method, $siteId, $baseline): void {
            $check((int)$type::find()->status(null)->siteId($siteId)->hasEvent($baseline + ['where' => '1=0'])->count() === 0, "$type must preserve trusted PHP filters inside an actual CP action callback.");
            $rendered = $app->view->renderString('{{ craft.events.' . $method . '({status: null, siteId: siteId, hasEvent: {status: null, siteId: siteId, where: "1=0"}}).count() }}', ['siteId' => $siteId]);
            $check((int)$rendered === 0, "$type must preserve trusted Twig filters inside an actual CP action callback.");
        };
        YiiEvent::on($controllerClass, $controllerClass::EVENT_BEFORE_ACTION, $callback);

        try {
            $input = $controllerClass === ElementSearchController::class ? 'search' : 'criteria';
            $expected = $input === 'search' ? min(5, $trustedTotal) : $trustedTotal;
            $check($request($type, $input, $baseline + ['where' => '1=0']) === $expected, "$type must clean submitted filters while preserving callback filters.");
        } finally {
            YiiEvent::off($controllerClass, $controllerClass::EVENT_BEFORE_ACTION, $callback);
        }
    }

    $app->controller = null;
    $app->request->setIsCpRequest(false);
    $rejected = false;

    try {
        $request($type, 'criteria', $baseline + ['where' => '1=0']);
    } catch (BadRequestHttpException $exception) {
        $rejected = true;
    } finally {
        $app->request->setIsCpRequest(true);
    }

    $check($rejected, "$type element indexes must retain Craft's CP-request requirement.");
}

echo "Passed $checks nested event criteria regression checks on PHP " . PHP_VERSION . ".\n";
