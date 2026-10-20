<?php

use craft\db\Connection;
use verbb\events\elements\Ticket;
use verbb\events\helpers\TicketCapacity;

$appPath = getenv('VERBB_TEST_APP');
$configFile = getenv('VERBB_TEST_PG_CONFIG');
$database = getenv('VERBB_TEST_PG_DATABASE');
if (!$appPath || !$configFile || !$database || !str_starts_with($database, 'events_capacity_')) {
    throw new RuntimeException('Set the disposable application and isolated events_capacity_ PostgreSQL database/config variables.');
}
$_SERVER['SCRIPT_FILENAME'] = $appPath . '/web/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
require $appPath . '/bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/web.php';
$ticket = new Ticket();
$ticket->id = 1;
$ticket->eventId = 1;
$ticket->sessionId = 1;
$ticket->typeId = 1;
$db = new Connection(json_decode(file_get_contents($configFile), true, 512, JSON_THROW_ON_ERROR));
$app->set('db', $db);
if ($db->createCommand('SELECT current_database()')->queryScalar() !== $database) {
    throw new RuntimeException('Wrong PostgreSQL database.');
}
$state = new ReflectionMethod(TicketCapacity::class, '_state');
$fits = new ReflectionMethod(TicketCapacity::class, '_fits');
$worker = ($argv[1] ?? null) === '--worker';
if ($worker) {
    $barrier = $argv[2];
    $id = (int)$argv[3];
    $isolation = $argv[4];
    if (!in_array($isolation, ['READ COMMITTED', 'REPEATABLE READ'], true)) {
        throw new RuntimeException('Unexpected isolation level.');
    }
    $tx = $db->beginTransaction($isolation);
    try {
        $state->invoke(null, [$ticket], false);
        file_put_contents($barrier . '.' . $id, 'ready');
        $deadline = microtime(true) + 20;
        while (!is_file($barrier . '.go')) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('PostgreSQL barrier timed out.');
            }
            usleep(20000);
        }
        TicketCapacity::lockEvents([1]);
        $current = $state->invoke(null, [$ticket], true);
        $allocated = $fits->invoke(null, [1 => 1], $current);
        if ($allocated) {
            $db->createCommand()->insert('elements', ['id' => $id, 'enabled' => true])->execute();
            $db->createCommand()->insert('events_purchased_tickets', ['id' => $id, 'eventId' => 1, 'sessionId' => 1, 'ticketId' => 1, 'ticketTypeId' => 1, 'reservationStatus' => 'active'])->execute();
        }
        usleep(200000);
        $tx->commit();
        echo json_encode(['allocated' => $allocated, 'serializationRejected' => false]) . "\n";
    } catch (Throwable $e) {
        if ($tx->isActive) {
            $tx->rollBack();
        }
        if ((string)$e->getCode() !== '40001') {
            throw $e;
        }
        echo json_encode(['allocated' => false, 'serializationRejected' => true]) . "\n";
    }
    exit;
}

// Minimal tables exercise the actual capacity queries and locks, not a rewritten algorithm.
$definitions = [
    'events_events' => 'id integer primary key, capacity integer',
    'events_sessions' => 'id integer primary key, capacity integer',
    'events_ticket_types' => 'id integer primary key, capacity integer, "seatsPerTicket" integer',
    'events_tickets' => 'id integer primary key, "eventId" integer, "sessionId" integer, "typeId" integer',
    'elements' => 'id integer primary key, enabled boolean, "dateDeleted" timestamp',
    'events_purchased_tickets' => 'id integer primary key, "eventId" integer, "sessionId" integer, "ticketId" integer, "ticketTypeId" integer, "orderId" integer, "lineItemId" integer, "reservationStatus" text',
];
foreach ($definitions as $table => $definition) {
    $db->createCommand("CREATE TABLE $table ($definition)")->execute();
}
$checks = 0;
try {
    foreach (['READ COMMITTED', 'REPEATABLE READ'] as $isolation) {
        foreach (array_reverse(array_keys($definitions)) as $table) {
            $db->createCommand("TRUNCATE TABLE $table")->execute();
        }
        foreach (['events_events', 'events_sessions'] as $table) {
            $db->createCommand()->insert($table, ['id' => 1, 'capacity' => 1])->execute();
        }
        $db->createCommand()->insert('events_ticket_types', ['id' => 1, 'capacity' => 1, 'seatsPerTicket' => 1])->execute();
        $db->createCommand()->insert('events_tickets', ['id' => 1, 'eventId' => 1, 'sessionId' => 1, 'typeId' => 1])->execute();
        $barrier = tempnam(sys_get_temp_dir(), 'events-capacity-pg-');
        unlink($barrier);
        $processes = [];
        try {
            foreach ([1, 2] as $id) {
                $process = proc_open([PHP_BINARY, __FILE__, '--worker', $barrier, (string)$id, $isolation], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                if (!is_resource($process)) {
                    throw new RuntimeException('Cannot start PostgreSQL worker.');
                }
                fclose($pipes[0]);
                $processes[] = [$process, $pipes];
            }
            $deadline = microtime(true) + 20;
            while (count(glob($barrier . '.*')) !== 2) {
                if (microtime(true) > $deadline) {
                    throw new RuntimeException('PostgreSQL workers did not reach the barrier.');
                }
                usleep(20000);
            }
            file_put_contents($barrier . '.go', 'go');
            $allocated = 0;
            foreach ($processes as [$process, $pipes]) {
                $output = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                if (proc_close($process) !== 0) {
                    throw new RuntimeException('PostgreSQL worker failed: ' . $output . $error);
                }
                $allocated += (int)json_decode(trim($output), true, 512, JSON_THROW_ON_ERROR)['allocated'];
            }
            $processes = [];
            if ($allocated !== 1 || (int)$db->createCommand('SELECT COUNT(*) FROM events_purchased_tickets')->queryScalar() !== 1) {
                throw new RuntimeException('PostgreSQL capacity must permit exactly one reservation under ' . $isolation);
            }
            $checks++;
        } finally {
            foreach ($processes as [$process, $pipes]) {
                if (is_resource($process)) {
                    proc_terminate($process);
                    proc_close($process);
                }
            }
            foreach (glob($barrier . '.*') as $file) {
                unlink($file);
            }
        }
    }
} finally {
    foreach (array_reverse(array_keys($definitions)) as $table) {
        $db->createCommand("DROP TABLE $table")->execute();
    }
}
echo "Passed $checks PostgreSQL capacity isolation checks on PHP " . PHP_VERSION . ".\n";
