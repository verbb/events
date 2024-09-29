<?php
namespace verbb\events\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;

class m240930_000000_qty extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%events_ticket_types}}', 'minQty')) {
            $this->addColumn('{{%events_ticket_types}}', 'minQty', $this->integer()->after('availableTo'));
        }

        if (!$this->db->columnExists('{{%events_ticket_types}}', 'maxQty')) {
            $this->addColumn('{{%events_ticket_types}}', 'maxQty', $this->integer()->after('minQty'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m240930_000000_qty cannot be reverted.\n";

        return false;
    }
}
