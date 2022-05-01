<?php
namespace verbb\events\records;

use craft\db\ActiveRecord;
use craft\records\Element;

use yii\db\ActiveQueryInterface;

class Event extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%events_events}}';
    }

    public function getType(): ActiveQueryInterface
    {
        return $this->hasOne(EventType::class, ['id' => 'typeId']);
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
