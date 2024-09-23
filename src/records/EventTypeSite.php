<?php
namespace verbb\events\records;

use craft\db\ActiveRecord;
use craft\records\Site;

use yii\db\ActiveQueryInterface;

class EventTypeSite extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%events_event_types_sites}}';
    }

    public function getEventType(): ActiveQueryInterface
    {
        return self::hasOne(EventType::class, ['id', 'eventTypeId']);
    }

    public function getSite(): ActiveQueryInterface
    {
        return self::hasOne(Site::class, ['id', 'siteId']);
    }
}
