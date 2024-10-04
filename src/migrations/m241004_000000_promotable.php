<?php
namespace verbb\events\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;

class m241004_000000_promotable extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%events_ticket_types}}', 'promotable')) {
            $this->addColumn('{{%events_ticket_types}}', 'promotable', $this->boolean()->notNull()->defaultValue(true)->after('maxQty'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m241004_000000_promotable cannot be reverted.\n";

        return false;
    }
}
