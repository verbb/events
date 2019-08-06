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

class m190806_000000_increase_capacity extends Migration
{
    public function safeUp()
    {
        $this->alterColumn('{{%events_events}}', 'capacity', $this->integer());
    }

    public function safeDown()
    {
        echo "m190806_000000_increase_capacity cannot be reverted.\n";
        return false;
    }
}
