<?php
namespace verbb\events\migrations;

use verbb\events\elements\Event;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;
use verbb\events\records\EventTypeSiteRecord;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\MigrationHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\StringHelper;

class m190420_000000_craft3_version extends Migration
{
    public function safeUp()
    {
        // Update all the Element references
        $this->update('{{%elements}}', ['type' => Event::class], ['type' => 'Events_Event']);
        $this->update('{{%elements}}', ['type' => Ticket::class], ['type' => 'Events_Ticket']);
        $this->update('{{%elements}}', ['type' => TicketType::class], ['type' => 'Events_TicketType']);

        if (!$this->db->columnExists('{{%events_events}}', 'postDate')) {
            $this->addColumn('{{%events_events}}', 'postDate', $this->dateTime());
        }

        if (!$this->db->columnExists('{{%events_events}}', 'expiryDate')) {
            $this->addColumn('{{%events_events}}', 'expiryDate', $this->dateTime());
        }

        if ($this->db->tableExists('{{%events_eventtypes_i18n}}')) {
            // Before messing with columns, it's much safer to drop all the FKs and indexes
            MigrationHelper::dropAllForeignKeysOnTable('{{%events_eventtypes_i18n}}');
            MigrationHelper::dropAllIndexesOnTable('{{%events_eventtypes_i18n}}');

            // Drop the old locale FK column and rename the new siteId FK column
            $this->dropColumn('{{%events_eventtypes_i18n}}', 'locale');
            MigrationHelper::renameColumn('{{%events_eventtypes_i18n}}', 'locale__siteId', 'siteId', $this);

            // And then just recreate them.
            $this->createIndex($this->db->getIndexName('{{%events_eventtypes_i18n}}', 'eventTypeId,siteId', true), '{{%events_eventtypes_i18n}}', 'eventTypeId,siteId', true);
            $this->createIndex($this->db->getIndexName('{{%events_eventtypes_i18n}}', 'siteId', false), '{{%events_eventtypes_i18n}}', 'siteId', false);
            $this->addForeignKey($this->db->getForeignKeyName('{{%events_eventtypes_i18n}}', 'siteId'), '{{%events_eventtypes_i18n}}', 'siteId', '{{%sites}}', 'id', 'CASCADE', 'CASCADE');
            $this->addForeignKey($this->db->getForeignKeyName('{{%events_eventtypes_i18n}}', 'eventTypeId'), '{{%events_eventtypes_i18n}}', 'eventTypeId', '{{%events_eventtypes}}', 'id', 'CASCADE', null);

            $this->addColumn('{{%events_eventtypes_i18n}}', 'template', $this->string(500));
            $this->addColumn('{{%events_eventtypes_i18n}}', 'hasUrls', $this->boolean());

            // Migrate hasUrls to be site specific
            $eventTypes = (new Query())->select('id, hasUrls, template')->from('{{%events_eventtypes}}')->all();

            foreach ($eventTypes as $eventType) {
                $eventTypeSites = (new Query())->select('*')->from('{{%events_eventtypes_i18n}}')->all();

                foreach ($eventTypeSites as $eventTypeSite) {
                    $eventTypeSite['template'] = $eventType['template'];
                    $eventTypeSite['hasUrls'] = $eventType['hasUrls'];
                    $this->update('{{%events_eventtypes_i18n}}', $eventTypeSite, ['id' => $eventTypeSite['id']]);
                }
            }
        }

        if ($this->db->columnExists('{{%events_eventtypes}}', 'template')) {
            $this->dropColumn('{{%events_eventtypes}}', 'template');
        }

        if ($this->db->columnExists('{{%events_eventtypes}}', 'hasUrls')) {
            $this->dropColumn('{{%events_eventtypes}}', 'hasUrls');
        }

        if ($this->db->tableExists('{{%events_eventtypes_i18n}}')) {
            MigrationHelper::renameTable('{{%events_eventtypes_i18n}}', EventTypeSiteRecord::tableName(), $this);
            MigrationHelper::renameColumn(EventTypeSiteRecord::tableName(), 'urlFormat', 'uriFormat', $this);
        }

        if ($this->db->columnExists('{{%events_tickets}}', 'ticketTypeId')) {
            MigrationHelper::renameColumn('{{%events_tickets}}', 'ticketTypeId', 'typeId', $this);
        }

        if (!$this->db->columnExists('{{%events_tickets}}', 'sortOrder')) {
            $this->addColumn('{{%events_tickets}}', 'sortOrder', $this->integer());
        }

        if (!$this->db->columnExists('{{%events_tickets}}', 'deletedWithEvent')) {
            $this->addColumn('{{%events_tickets}}', 'deletedWithEvent', $this->integer()->null());
        }
    }

    public function safeDown()
    {
        echo "m190420_000000_craft3_version cannot be reverted.\n";
        return false;
    }
}