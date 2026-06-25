<?php
namespace verbb\events\migrations;

use craft\db\Migration;

class m260625_000000_ticket_updates extends Migration
{
    // Public Methods
    // =========================================================================

    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%events_ticket_updates}}')) {
            $this->createTable('{{%events_ticket_updates}}', [
                'id' => $this->primaryKey(),
                'eventId' => $this->integer()->notNull(),
                'status' => $this->string()->notNull(),
                'progress' => $this->float()->notNull()->defaultValue(0),
                'description' => $this->text(),
                'error' => $this->text(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, '{{%events_ticket_updates}}', ['eventId']);
            $this->createIndex(null, '{{%events_ticket_updates}}', ['status']);

            $this->addForeignKey(
                null,
                '{{%events_ticket_updates}}',
                'eventId',
                '{{%events_events}}',
                'id',
                'CASCADE',
                null,
            );
        }

        return true;
    }

    public function safeDown(): bool
    {
        if ($this->db->tableExists('{{%events_ticket_updates}}')) {
            $this->dropTableIfExists('{{%events_ticket_updates}}');
        }

        return true;
    }
}
