<?php
namespace verbb\events\migrations;

use verbb\events\elements\Event;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;
use verbb\events\records\TicketTypeRecord;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\MigrationHelper;
use craft\helpers\Component as ComponentHelper;
use craft\helpers\StringHelper;

class m204014_000000_cleanup_columns extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('{{%events_eventtypes}}', 'dateCreated', $this->dateTime()->notNull() . " AFTER icsLocationFieldHandle");
        $this->alterColumn('{{%events_eventtypes}}', 'dateUpdated', $this->dateTime()->notNull() . " AFTER dateCreated");
        $this->alterColumn('{{%events_eventtypes}}', 'uid', $this->uid() . " AFTER dateUpdated");
    }

    public function safeDown()
    {
        echo "m204014_000000_cleanup_columns cannot be reverted.\n";
        return false;
    }
}
