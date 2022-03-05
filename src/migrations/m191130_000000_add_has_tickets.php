<?php
namespace verbb\events\migrations;

use craft\db\Migration;

class m191130_000000_add_has_tickets extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%events_eventtypes}}', 'hasTickets')) {
            $this->addColumn('{{%events_eventtypes}}', 'hasTickets', $this->boolean()->defaultValue(true)->notNull());
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m191130_000000_add_has_tickets cannot be reverted.\n";
        return false;
    }
}
