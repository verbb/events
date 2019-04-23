<?php
namespace verbb\events\records;

use craft\db\ActiveRecord;
use craft\records\Element;

use yii\db\ActiveQueryInterface;

class TicketRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%events_tickets}}';
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getEvent(): ActiveQueryInterface
    {
        return $this->hasOne(EventRecord::class, ['id' => 'eventId']);
    }

    public function getType(): ActiveQueryInterface
    {
        return $this->hasOne(TicketTypeRecord::class, ['id' => 'typeId']);
    }
}
