<?php
namespace verbb\events\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;

class Install extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        $this->createTables();
        $this->createIndexes();
        $this->addForeignKeys();

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropProjectConfig();
        $this->dropForeignKeys();
        $this->dropTables();

        return true;
    }

    public function createTables(): void
    {
        $this->archiveTableIfExists('{{%events_event_types}}');
        $this->createTable('{{%events_event_types}}', [
            'id' => $this->primaryKey(),
            'fieldLayoutId' => $this->integer(),
            'sessionFieldLayoutId' => $this->integer(),
            'ticketTypeFieldLayoutId' => $this->integer(),
            'name' => $this->string()->notNull(),
            'handle' => $this->string()->notNull(),
            'enableVersioning' => $this->boolean()->defaultValue(false),
            'sessionTitleFormat' => $this->string(),
            'ticketTitleFormat' => $this->string(),
            'ticketSkuFormat' => $this->string(),
            'purchasedTicketTitleFormat' => $this->string(),
            'icsTimezone' => $this->string(),
            'icsDescriptionFieldHandle' => $this->string(),
            'icsLocationFieldHandle' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%events_event_types_sites}}');
        $this->createTable('{{%events_event_types_sites}}', [
            'id' => $this->primaryKey(),
            'eventTypeId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'uriFormat' => $this->text(),
            'template' => $this->string(500),
            'hasUrls' => $this->boolean()->notNull()->defaultValue(false),
            'enabledByDefault' => $this->boolean()->defaultValue(true)->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%events_events}}');
        $this->createTable('{{%events_events}}', [
            'id' => $this->primaryKey(),
            'typeId' => $this->integer(),
            'capacity' => $this->integer(),
            'postDate' => $this->dateTime(),
            'expiryDate' => $this->dateTime(),
            'ticketsCache' => $this->string(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%events_purchased_tickets}}');
        $this->createTable('{{%events_purchased_tickets}}', [
            'id' => $this->primaryKey(),
            'eventId' => $this->integer(),
            'sessionId' => $this->integer(),
            'ticketId' => $this->integer(),
            'ticketTypeId' => $this->integer(),
            'orderId' => $this->integer(),
            'lineItemId' => $this->integer(),
            'checkedIn' => $this->boolean(),
            'checkedInDate' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%events_sessions}}');
        $this->createTable('{{%events_sessions}}', [
            'id' => $this->primaryKey(),
            'primaryOwnerId' => $this->integer(),
            'startDate' => $this->dateTime(),
            'endDate' => $this->dateTime(),
            'allDay' => $this->boolean(),
            'groupUid' => $this->char(36),
            'deletedWithEvent' => $this->boolean()->notNull()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%events_ticket_types}}');
        $this->createTable('{{%events_ticket_types}}', [
            'id' => $this->primaryKey(),
            'primaryOwnerId' => $this->integer(),
            'price' => $this->decimal(14, 4),
            'capacity' => $this->integer(),
            'availableFrom' => $this->dateTime(),
            'availableTo' => $this->dateTime(),
            'minQty' => $this->integer(),
            'maxQty' => $this->integer(),
            'legacyTicketId' => $this->string(),
            'legacyTicketTypeId' => $this->string(),
            'deletedWithEvent' => $this->boolean()->notNull()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->archiveTableIfExists('{{%events_tickets}}');
        $this->createTable('{{%events_tickets}}', [
            'id' => $this->primaryKey(),
            'eventId' => $this->integer(),
            'sessionId' => $this->integer(),
            'typeId' => $this->integer(),
            'deletedWithEvent' => $this->boolean()->notNull()->defaultValue(false),
            'deletedWithSession' =>$this->boolean()->notNull()->defaultValue(false),
            'deletedWithType' => $this->boolean()->notNull()->defaultValue(false),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
    }

    public function createIndexes(): void
    {
        $this->createIndex(null, '{{%events_event_types}}', 'handle', true);
        $this->createIndex(null, '{{%events_event_types}}', 'fieldLayoutId', false);
        $this->createIndex(null, '{{%events_event_types}}', 'sessionFieldLayoutId', false);
        $this->createIndex(null, '{{%events_event_types}}', 'ticketTypeFieldLayoutId', false);

        $this->createIndex(null, '{{%events_event_types_sites}}', 'eventTypeId,siteId', true);
        $this->createIndex(null, '{{%events_event_types_sites}}', 'siteId', false);

        $this->createIndex(null, '{{%events_events}}', 'typeId', false);

        $this->createIndex(null, '{{%events_purchased_tickets}}', 'eventId', false);
        $this->createIndex(null, '{{%events_purchased_tickets}}', 'sessionId', false);
        $this->createIndex(null, '{{%events_purchased_tickets}}', 'ticketId', false);
        $this->createIndex(null, '{{%events_purchased_tickets}}', 'ticketTypeId', false);
        $this->createIndex(null, '{{%events_purchased_tickets}}', 'orderId', false);
        $this->createIndex(null, '{{%events_purchased_tickets}}', 'lineItemId', false);

        $this->createIndex(null, '{{%events_sessions}}', 'primaryOwnerId', false);

        $this->createIndex(null, '{{%events_ticket_types}}', 'primaryOwnerId', false);

        $this->createIndex(null, '{{%events_tickets}}', 'eventId', false);
        $this->createIndex(null, '{{%events_tickets}}', 'sessionId', false);
        $this->createIndex(null, '{{%events_tickets}}', 'typeId', false);
    }

    public function addForeignKeys(): void
    {
        $this->addForeignKey(null, '{{%events_event_types}}', 'fieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%events_event_types}}', 'sessionFieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%events_event_types}}', 'ticketTypeFieldLayoutId', '{{%fieldlayouts}}', 'id', 'SET NULL', null);

        $this->addForeignKey(null, '{{%events_event_types_sites}}', 'eventTypeId', '{{%events_event_types}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_event_types_sites}}', 'siteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');

        $this->addForeignKey(null, '{{%events_events}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_events}}', 'typeId', '{{%events_event_types}}', 'id', 'CASCADE', null);

        $this->addForeignKey(null, '{{%events_purchased_tickets}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_purchased_tickets}}', 'eventId', '{{%events_events}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%events_purchased_tickets}}', 'sessionId', '{{%events_sessions}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%events_purchased_tickets}}', 'ticketId', '{{%events_tickets}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%events_purchased_tickets}}', 'ticketTypeId', '{{%events_ticket_types}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%events_purchased_tickets}}', 'lineItemId', '{{%commerce_lineitems}}', 'id', 'SET NULL', null);
        $this->addForeignKey(null, '{{%events_purchased_tickets}}', 'orderId', '{{%commerce_orders}}', 'id', 'SET NULL', null);

        $this->addForeignKey(null, '{{%events_sessions}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_sessions}}', 'primaryOwnerId', '{{%events_events}}', 'id', 'CASCADE', null);

        $this->addForeignKey(null, '{{%events_ticket_types}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_ticket_types}}', 'primaryOwnerId', '{{%events_events}}', 'id', 'CASCADE', null);

        $this->addForeignKey(null, '{{%events_tickets}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_tickets}}', 'eventId', '{{%events_events}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_tickets}}', 'sessionId', '{{%events_sessions}}', 'id', 'CASCADE', null);
        $this->addForeignKey(null, '{{%events_tickets}}', 'typeId', '{{%events_ticket_types}}', 'id', 'CASCADE', null);
    }

    public function dropTables(): void
    {
        $this->dropTableIfExists('{{%events_event_types}}');
        $this->dropTableIfExists('{{%events_event_types_sites}}');
        $this->dropTableIfExists('{{%events_events}}');
        $this->dropTableIfExists('{{%events_purchased_tickets}}');
        $this->dropTableIfExists('{{%events_sessions}}');
        $this->dropTableIfExists('{{%events_ticket_types}}');
        $this->dropTableIfExists('{{%events_tickets}}');
    }

    public function dropForeignKeys(): void
    {
        if ($this->db->tableExists('{{%events_event_types}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%events_event_types}}', $this);
            MigrationHelper::dropAllForeignKeysToTable('{{%events_event_types}}', $this);
        }

        if ($this->db->tableExists('{{%events_event_types_sites}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%events_event_types_sites}}', $this);
            MigrationHelper::dropAllForeignKeysToTable('{{%events_event_types_sites}}', $this);
        }

        if ($this->db->tableExists('{{%events_events}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%events_events}}', $this);
            MigrationHelper::dropAllForeignKeysToTable('{{%events_events}}', $this);
        }

        if ($this->db->tableExists('{{%events_purchased_tickets}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%events_purchased_tickets}}', $this);
            MigrationHelper::dropAllForeignKeysToTable('{{%events_purchased_tickets}}', $this);
        }

        if ($this->db->tableExists('{{%events_ticket_types}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%events_ticket_types}}', $this);
            MigrationHelper::dropAllForeignKeysToTable('{{%events_ticket_types}}', $this);
        }

        if ($this->db->tableExists('{{%events_tickets}}')) {
            MigrationHelper::dropAllForeignKeysOnTable('{{%events_tickets}}', $this);
            MigrationHelper::dropAllForeignKeysToTable('{{%events_tickets}}', $this);
        }
    }

    public function dropProjectConfig(): void
    {
        Craft::$app->getProjectConfig()->remove('events');
    }
}
