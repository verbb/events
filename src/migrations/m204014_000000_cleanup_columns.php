<?php
namespace verbb\events\migrations;

use craft\db\Migration;

class m204014_000000_cleanup_columns extends Migration
{
    public function safeUp(): bool
    {   
        if ($this->db->getIsMysql()) {
            $this->alterColumn('{{%events_eventtypes}}', 'dateCreated', $this->dateTime()->notNull() . " AFTER icsLocationFieldHandle");
            $this->alterColumn('{{%events_eventtypes}}', 'dateUpdated', $this->dateTime()->notNull() . " AFTER dateCreated");
            $this->alterColumn('{{%events_eventtypes}}', 'uid', $this->uid() . " AFTER dateUpdated");
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m204014_000000_cleanup_columns cannot be reverted.\n";
        return false;
    }
}
