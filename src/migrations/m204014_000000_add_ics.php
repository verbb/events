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

class m204014_000000_add_ics extends Migration
{
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%events_eventtypes}}', 'icsTimezone')) {
            $this->addColumn('{{%events_eventtypes}}', 'icsTimezone', $this->string()->after('hasTickets'));
        }

        if (!$this->db->columnExists('{{%events_eventtypes}}', 'icsDescriptionFieldHandle')) {
            $this->addColumn('{{%events_eventtypes}}', 'icsDescriptionFieldHandle', $this->string()->after('icsTimezone'));
        }

        if (!$this->db->columnExists('{{%events_eventtypes}}', 'icsLocationFieldHandle')) {
            $this->addColumn('{{%events_eventtypes}}', 'icsLocationFieldHandle', $this->string()->after('icsDescriptionFieldHandle'));
        }
    }

    public function safeDown()
    {
        echo "m204014_000000_add_ics cannot be reverted.\n";
        return false;
    }
}
