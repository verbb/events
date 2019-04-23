<?php
namespace verbb\events\records;

use craft\db\ActiveRecord;
use craft\records\Site;

use yii\db\ActiveQueryInterface;

class EventTypeSiteRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%events_eventtypes_sites}}';
    }

    public function getEventType(): ActiveQueryInterface
    {
        return $this->hasOne(EventTypeRecord::class, ['id', 'eventTypeId']);
    }

    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id', 'siteId']);
    }
}
