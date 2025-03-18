<?php
namespace verbb\events\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use craft\helpers\MigrationHelper;

class m250318_000000_shipping_tax extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->columnExists('{{%events_event_types}}', 'taxCategoryId')) {
            $this->addColumn('{{%events_event_types}}', 'taxCategoryId', $this->integer()->after('icsLocationFieldHandle'));

            $this->addForeignKey(null, '{{%events_event_types}}', 'taxCategoryId', '{{%commerce_taxcategories}}', 'id', 'SET NULL', null);
        }
            
        if (!$this->db->columnExists('{{%events_event_types}}', 'shippingCategoryId')) {
            $this->addColumn('{{%events_event_types}}', 'shippingCategoryId', $this->integer()->after('taxCategoryId'));

            $this->addForeignKey(null, '{{%events_event_types}}', 'shippingCategoryId', '{{%commerce_shippingcategories}}', 'id', 'SET NULL', null);
        }

        return true;
    }

    public function safeDown(): bool
    {
        echo "m250318_000000_shipping_tax cannot be reverted.\n";

        return false;
    }
}
