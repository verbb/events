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

class m190511_000000_add_title_format extends Migration
{
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%events_eventtypes}}', 'hasTitleField')) {
            $this->addColumn('{{%events_eventtypes}}', 'hasTitleField', $this->boolean()->defaultValue(true)->notNull());
        }

        if (!$this->db->columnExists('{{%events_eventtypes}}', 'titleLabel')) {
            $this->addColumn('{{%events_eventtypes}}', 'titleLabel', $this->string()->defaultValue('Title'));
        }

        if (!$this->db->columnExists('{{%events_eventtypes}}', 'titleFormat')) {
            $this->addColumn('{{%events_eventtypes}}', 'titleFormat', $this->string());
        }
    }

    public function safeDown()
    {
        echo "m190511_000000_add_title_format cannot be reverted.\n";
        return false;
    }
}
