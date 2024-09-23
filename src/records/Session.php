<?php
namespace verbb\events\records;

use verbb\events\elements\Event;

use craft\db\ActiveRecord;
use craft\records\Element;

use yii\db\ActiveQueryInterface;

class Session extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%events_sessions}}';
    }

    public function getProduct(): ActiveQueryInterface
    {
        return self::hasOne(Event::class, ['id', 'eventId']);
    }

    public function getElement(): ActiveQueryInterface
    {
        return self::hasOne(Element::class, ['id', 'id']);
    }
}
