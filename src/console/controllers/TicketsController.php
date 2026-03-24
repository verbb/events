<?php
namespace verbb\events\console\controllers;

use verbb\events\elements\Ticket;

use Craft;
use craft\console\Controller;
use craft\db\Query;
use craft\db\Table as CraftTable;
use craft\helpers\Console;

use yii\console\ExitCode;

class TicketsController extends Controller
{
    // Properties
    // =========================================================================

    public bool $dryRun = true;


    // Public Methods
    // =========================================================================

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'dryRun';

        return $options;
    }

    /**
     * Lists ticket purchasables whose event, session, or ticket type no longer exists or is soft-deleted.
     *
     * With `--dry-run=0`, soft-deletes those tickets only when they have no purchased tickets (same rules as the CP).
     */
    public function actionCleanupOrphaned(): int
    {
        $ids = $this->_orphanedTicketIds();

        if ($ids === []) {
            $this->stdout('No orphaned tickets found.' . PHP_EOL, Console::FG_GREEN);

            return ExitCode::OK;
        }

        $this->stdout(count($ids) . ' orphaned ticket(s) found (missing or trashed event/session/ticket type).' . PHP_EOL);

        $elementsService = Craft::$app->getElements();
        $skippedPurchased = 0;
        $deleted = 0;

        foreach ($ids as $id) {
            $ticket = Ticket::find()->id($id)->status(null)->one();

            if (!$ticket) {
                continue;
            }

            if ($ticket->getPurchasedTicketsCount() > 0) {
                $this->stderr("Skipped ticket #{$id}: still referenced by purchased tickets." . PHP_EOL, Console::FG_YELLOW);
                $skippedPurchased++;

                continue;
            }

            if ($this->dryRun) {
                $this->stdout("Would soft-delete ticket #{$id}" . PHP_EOL);

                continue;
            }

            if ($elementsService->deleteElement($ticket)) {
                $this->stdout("Soft-deleted ticket #{$id}" . PHP_EOL, Console::FG_GREEN);
                $deleted++;
            } else {
                $this->stderr("Could not delete ticket #{$id}." . PHP_EOL, Console::FG_RED);
            }
        }

        if ($this->dryRun) {
            $this->stdout(PHP_EOL . 'Dry run only. Re-run with --dry-run=0 to soft-delete tickets (except those with purchases).' . PHP_EOL);
        } else {
            $this->stdout(PHP_EOL . "Deleted {$deleted} ticket(s). Skipped {$skippedPurchased} with purchases." . PHP_EOL);
        }

        return ExitCode::OK;
    }


    // Private Methods
    // =========================================================================

    private function _orphanedTicketIds(): array
    {
        $query = (new Query())
            ->select(['et.id'])
            ->from(['et' => '{{%events_tickets}}'])
            ->innerJoin(['tel' => CraftTable::ELEMENTS], [
                'and',
                '[[tel.id]] = [[et.id]]',
                ['tel.dateDeleted' => null],
            ])
            ->leftJoin(['e' => CraftTable::ELEMENTS], [
                'and',
                '[[e.id]] = [[et.eventId]]',
                ['e.dateDeleted' => null],
            ])
            ->leftJoin(['s' => CraftTable::ELEMENTS], [
                'and',
                '[[s.id]] = [[et.sessionId]]',
                ['s.dateDeleted' => null],
            ])
            ->leftJoin(['t' => CraftTable::ELEMENTS], [
                'and',
                '[[t.id]] = [[et.typeId]]',
                ['t.dateDeleted' => null],
            ])
            ->where([
                'or',
                ['e.id' => null],
                ['s.id' => null],
                ['t.id' => null],
            ])
            ->orderBy(['et.id' => SORT_ASC]);

        return $query->column();
    }
}
