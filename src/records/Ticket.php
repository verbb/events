<?php
namespace verbb\events\records;

use verbb\events\records\Event as EventRecord;
use verbb\events\records\TicketType as TicketTypeRecord;

use craft\db\ActiveRecord;
use craft\records\Element;

use yii\db\ActiveQueryInterface;

class Ticket extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%events_tickets}}';
    }

    public function getElement(): ActiveQueryInterface
    {
        return self::hasOne(Element::class, ['id' => 'id']);
    }

    public function getEvent(): ActiveQueryInterface
    {
        return self::hasOne(EventRecord::class, ['id' => 'eventId']);
    }

    public function getType(): ActiveQueryInterface
    {
        return self::hasOne(TicketTypeRecord::class, ['id' => 'typeId']);
    }
}
