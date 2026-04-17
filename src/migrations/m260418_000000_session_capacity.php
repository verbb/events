<?php
namespace verbb\events\migrations;

use craft\db\Migration;

class m260418_000000_session_capacity extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%events_sessions}}', 'capacity')) {
            $this->addColumn('{{%events_sessions}}', 'capacity', $this->integer()->after('allDay'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260418_000000_session_capacity cannot be reverted.\n";
        return false;
    }
}
