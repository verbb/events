<?php
namespace verbb\events\migrations;

use craft\db\Migration;

class m190806_000000_increase_capacity extends Migration
{
    public function safeUp(): bool
    {
        $this->alterColumn('{{%events_events}}', 'capacity', $this->integer());

        return true;
    }

    public function safeDown(): bool
    {
        echo "m190806_000000_increase_capacity cannot be reverted.\n";
        return false;
    }
}
