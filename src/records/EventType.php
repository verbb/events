<?php
namespace verbb\events\records;

use craft\db\ActiveRecord;
use craft\records\FieldLayout;

use yii\db\ActiveQueryInterface;

class EventType extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%events_event_types}}';
    }

    public function getFieldLayout(): ActiveQueryInterface
    {
        return self::hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }
}
