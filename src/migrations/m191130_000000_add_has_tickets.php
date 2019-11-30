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

class m191130_000000_add_has_tickets extends Migration
{
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%events_eventtypes}}', 'hasTickets')) {
            $this->addColumn('{{%events_eventtypes}}', 'hasTickets', $this->boolean()->defaultValue(true)->notNull());
        }
    }

    public function safeDown()
    {
        echo "m191130_000000_add_has_tickets cannot be reverted.\n";
        return false;
    }
}
