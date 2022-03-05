<?php
namespace verbb\events\migrations;

use craft\db\Migration;

class m190511_000000_add_title_format extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%events_eventtypes}}', 'hasTitleField')) {
            $this->addColumn('{{%events_eventtypes}}', 'hasTitleField', $this->boolean()->defaultValue(true)->notNull());
        }

        if (!$this->db->columnExists('{{%events_eventtypes}}', 'titleLabel')) {
            $this->addColumn('{{%events_eventtypes}}', 'titleLabel', $this->string()->defaultValue('Title'));
        }

        if (!$this->db->columnExists('{{%events_eventtypes}}', 'titleFormat')) {
            $this->addColumn('{{%events_eventtypes}}', 'titleFormat', $this->string());
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190511_000000_add_title_format cannot be reverted.\n";
        return false;
    }
}
