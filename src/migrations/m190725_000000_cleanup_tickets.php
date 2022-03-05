<?php
namespace verbb\events\migrations;

use verbb\events\elements\Ticket;
use verbb\events\records\TicketTypeRecord;

use Craft;
use craft\db\Migration;

class m190725_000000_cleanup_tickets extends Migration
{
    public function safeUp(): bool
    {
        $trashedTicketTypes = TicketTypeRecord::find()
            ->innerJoinWith(['element element'])
            ->where(['not', ['element.dateDeleted' => null]])
            ->all();

        foreach ($trashedTicketTypes as $trashedTicketType) {
            $tickets = Ticket::find()
                ->typeId($trashedTicketType->id)
                ->anyStatus()
                ->limit(null)
                ->all();

            foreach ($tickets as $ticket) {
                Craft::$app->getElements()->deleteElement($ticket);
            }
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190725_000000_cleanup_tickets cannot be reverted.\n";
        return false;
    }
}
