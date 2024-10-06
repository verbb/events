<?php
namespace verbb\events\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;

class m241006_000000_seats extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%events_ticket_types}}', 'seatsPerTicket')) {
            $this->addColumn('{{%events_ticket_types}}', 'seatsPerTicket', $this->integer()->after('promotable'));
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m241006_000000_seats cannot be reverted.\n";

        return false;
    }
}
