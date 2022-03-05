<?php
namespace verbb\events\migrations;

use craft\db\Migration;

class m204014_000000_add_ics extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%events_eventtypes}}', 'icsTimezone')) {
            $this->addColumn('{{%events_eventtypes}}', 'icsTimezone', $this->string()->after('hasTickets'));
        }

        if (!$this->db->columnExists('{{%events_eventtypes}}', 'icsDescriptionFieldHandle')) {
            $this->addColumn('{{%events_eventtypes}}', 'icsDescriptionFieldHandle', $this->string()->after('icsTimezone'));
        }

        if (!$this->db->columnExists('{{%events_eventtypes}}', 'icsLocationFieldHandle')) {
            $this->addColumn('{{%events_eventtypes}}', 'icsLocationFieldHandle', $this->string()->after('icsDescriptionFieldHandle'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m204014_000000_add_ics cannot be reverted.\n";
        return false;
    }
}
