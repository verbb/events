<?php
namespace verbb\events\console\controllers;

use verbb\events\Events;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;

use yii\console\ExitCode;

class PurchasedTicketsController extends Controller
{
    // Properties
    // =========================================================================

    public bool $dryRun = true;
    public ?int $eventId = null;
    public ?int $olderThan = null;


    // Public Methods
    // =========================================================================

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'dryRun';
        $options[] = 'eventId';
        $options[] = 'olderThan';

        return $options;
    }

    /**
     * Permanently deletes trashed purchased tickets.
     *
     * With `--dry-run=0`, hard-deletes trashed purchased tickets. Use `--event-id=` to scope to an event,
     * and `--older-than=` to only purge tickets trashed at least that many days ago.
     */
    public function actionPurgeTrashed(): int
    {
        $result = Events::$plugin->getPurchasedTickets()->purgeTrashedPurchasedTickets([
            'dryRun' => $this->dryRun,
            'eventId' => $this->eventId,
            'olderThanDays' => $this->olderThan,
        ]);

        $count = count($result['purchasedTicketIds']);

        if ($count === 0) {
            $this->stdout('No trashed purchased tickets found to purge.' . PHP_EOL, Console::FG_GREEN);

            return ExitCode::OK;
        }

        if ($this->dryRun) {
            $this->stdout("Would permanently delete {$count} trashed purchased ticket(s)." . PHP_EOL);

            if ($result['skipped']) {
                $this->stdout("Skipped {$result['skipped']} trashed purchased ticket(s) that do not match the retention filter." . PHP_EOL, Console::FG_YELLOW);
            }

            $this->stdout(PHP_EOL . 'Dry run only. Re-run with --dry-run=0 to permanently delete trashed purchased tickets.' . PHP_EOL);

            return ExitCode::OK;
        }

        $this->stdout("Permanently deleted {$result['purged']} trashed purchased ticket(s)." . PHP_EOL, Console::FG_GREEN);

        if ($result['skipped']) {
            $this->stdout("Skipped {$result['skipped']} trashed purchased ticket(s)." . PHP_EOL, Console::FG_YELLOW);
        }

        return ExitCode::OK;
    }
}
