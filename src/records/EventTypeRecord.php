<?php
namespace verbb\events\records;

use craft\db\ActiveRecord;
use craft\records\FieldLayout;

use yii\db\ActiveQueryInterface;

class EventTypeRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%events_eventtypes}}';
    }

    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }
}
