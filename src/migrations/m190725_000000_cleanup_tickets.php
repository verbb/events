<?php
namespace verbb\events\migrations;

use verbb\events\elements\Event;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;
use verbb\events\records\TicketTypeRecord;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\MigrationHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\StringHelper;

class m190725_000000_cleanup_tickets extends Migration
{
    public function safeUp()
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
    }

    public function safeDown()
    {
        echo "m190725_000000_cleanup_tickets cannot be reverted.\n";
        return false;
    }
}
