<?php
namespace verbb\events\records;

use craft\db\ActiveRecord;
use craft\records\Element;

use yii\db\ActiveQueryInterface;

class TicketType extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%events_ticket_types}}';
    }

    public function getElement(): ActiveQueryInterface
    {
        return self::hasOne(Element::class, ['id' => 'id']);
    }

    public function getEvent(): ActiveQueryInterface
    {
        return self::hasOne(EventRecord::class, ['id' => 'eventId']);
    }
}
