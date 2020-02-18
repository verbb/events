<?php
namespace verbb\events\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp()
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();

        return true;
    }

    public function safeDown()
    {
        $this->dropForeignKeys();
        $this->dropTables();

        return true;
    }
    
    // Protected Methods
    // =========================================================================

    protected function createTables()
    {
        $this->createTable('{{%events_events}}', [
            'id' => $this->primaryKey(),
            'typeId' => $this->integer(),
            'allDay' => $this->boolean(),
            'capacity' => $this->integer(),
            'startDate' => $this->dateTime(),
            'endDate' => $this->dateTime(),
            'postDate' => $this->dateTime(),
            'expiryDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%events_eventtypes}}', [
            'id' => $this->primaryKey(),
            'fieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'hasTitleField' => $this->boolean()->defaultValue(true)->notNull(),
            'titleLabel' => $this->string()->defaultValue('Title'),
            'titleFormat' => $this->string(),
            'hasTickets' => $this->boolean()->defaultValue(true)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%events_eventtypes_sites}}', [
            'id' => $this->primaryKey(),
            'eventTypeId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'uriFormat' => $this->text(),
            'template' => $this->string(500),
            'hasUrls' => $this->boolean(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%events_tickets}}', [
            'id' => $this->primaryKey(),
            'eventId' => $this->integer(),
            'typeId' => $this->integer(),
            'sku' => $this->string()->notNull(),
            'quantity' => $this->integer(),
            'price' => $this->decimal(14, 4)->notNull(),
            'availableFrom' => $this->dateTime(),
            'availableTo' => $this->dateTime(),
            'sortOrder' => $this->integer(),
            'deletedWithEvent' => $this->integer()->null(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%events_tickettypes}}', [
            'id' => $this->primaryKey(),
            'taxCategoryId' => $this->integer()->notNull(),
            'shippingCategoryId' => $this->integer()->notNull(),
            'fieldLayoutId' => $this->integer(),
            'handle' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createTable('{{%events_purchasedtickets}}', [
            'id' => $this->primaryKey(),
            'eventId' => $this->integer(),
            'ticketId' => $this->integer(),
            'orderId' => $this->integer(),
            'lineItemId' => $this->integer(),
            'ticketSku' => $this->string(),
            'checkedIn' => $this->boolean(),
            'checkedInDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);        
    }
    
    protected function dropTables()
    {
        $this->dropTable('{{%events_events}}');
        $this->dropTable('{{%events_eventtypes}}');
        $this->dropTable('{{%events_eventtypes_sites}}');
        $this->dropTable('{{%events_purchasedtickets}}');
        $this->dropTable('{{%events_tickets}}');
        $this->dropTable('{{%events_tickettypes}}');
    }
    
    protected function createIndexes()
    {
        $this->createIndex(null, '{{%events_events}}', 'typeId', false);
        
        $this->createIndex(null, '{{%events_eventtypes}}', 'handle', true);
        $this->createIndex(null, '{{%events_eventtypes}}', 'fieldLayoutId', false);
        
        $this->createIndex(null, '{{%events_eventtypes_sites}}', 'eventTypeId,siteId', true);
        $this->createIndex(null, '{{%events_eventtypes_sites}}', 'siteId', false);
        
        $this->createIndex(null, '{{%events_purchasedtickets}}', 'eventId', false);
        $this->createIndex(null, '{{%events_purchasedtickets}}', 'ticketId', false);
        $this->createIndex(null, '{{%events_purchasedtickets}}', 'orderId', false);
        $this->createIndex(null, '{{%events_purchasedtickets}}', 'lineItemId', false);
        
        $this->createIndex(null, '{{%events_tickets}}', 'sku', true);
        $this->createIndex(null, '{{%events_tickets}}', 'eventId', false);
        $this->createIndex(null, '{{%events_tickets}}', 'typeId', false);
        
        $this->createIndex(null, '{{%events_tickettypes}}', 'handle', true);
        $this->createIndex(null, '{{%events_tickettypes}}', 'taxCategoryId', false);
        $this->createIndex(null, '{{%events_tickettypes}}', 'shippingCategoryId', false);
        $this->createIndex(null, '{{%events_tickettypes}}', 'fieldLayoutId', false);
    }

    protected function addForeignKeys()
    {
        $this->addForeignKey(null, '{{%events_events}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_events}}', 'typeId', '{{%events_eventtypes}}', 'id', 'CASCADE', null);
        
        $this->addForeignKey(null, '{{%events_eventtypes}}', 'fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);
        
        $this->addForeignKey(null, '{{%events_eventtypes_sites}}', 'eventTypeId', '{{%events_eventtypes}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_eventtypes_sites}}', 'siteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');
        
        $this->addForeignKey(null, '{{%events_purchasedtickets}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_purchasedtickets}}', 'eventId', '{{%events_events}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%events_purchasedtickets}}', 'lineItemId', '{{%commerce_lineitems}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%events_purchasedtickets}}', 'orderId', '{{%commerce_orders}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%events_purchasedtickets}}', 'ticketId', '{{%events_tickets}}', 'id', 'SET NULL', null);
        
        $this->addForeignKey(null, '{{%events_tickets}}', 'eventId', '{{%events_events}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_tickets}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_tickets}}', 'typeId', '{{%events_tickettypes}}', 'id', 'CASCADE', null);
        
        $this->addForeignKey(null, '{{%events_tickettypes}}', 'fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%events_tickettypes}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_tickettypes}}', 'shippingCategoryId', '{{%commerce_shippingcategories}}', 'id', null, null);
        $this->addForeignKey(null, '{{%events_tickettypes}}', 'taxCategoryId', '{{%commerce_taxcategories}}', 'id', null, null);
    }
    
    protected function dropForeignKeys()
    {
        MigrationHelper::dropAllForeignKeysOnTable('{{%events_events}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%events_eventtypes}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%events_eventtypes_sites}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%events_purchasedtickets}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%events_tickets}}', $this);
        MigrationHelper::dropAllForeignKeysOnTable('{{%events_tickettypes}}', $this);
    }
}
