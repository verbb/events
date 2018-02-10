<?php

namespace Craft;

/**
 * @property int    id
 * @property int    eventTypeId
 * @property string locale
 * @property string urlFormat
 */
class Events_EventTypeLocaleRecord extends BaseRecord
{

    // Public Methods
    // =========================================================================

    public function getTableName()
    {
        return 'events_eventtypes_i18n';
    }

    public function defineRelations()
    {
        return [
            'eventType' => [
                static::BELONGS_TO,
                'Events_EventTypeRecord',
                'required' => true,
                'onDelete' => static::CASCADE,
            ],
            'locale'    => [
                static::BELONGS_TO,
                'LocaleRecord',
                'locale',
                'required' => true,
                'onDelete' => static::CASCADE,
                'onUpdate' => static::CASCADE,
            ],
        ];
    }

    public function defineIndexes()
    {
        return [
            ['columns' => ['eventTypeId', 'locale'], 'unique' => true],
        ];
    }

    // Protected Methods
    // =========================================================================

    protected function defineAttributes()
    {
        return [
            'locale'    => [AttributeType::Locale, 'required' => true],
            'urlFormat' => AttributeType::UrlFormat,
        ];
    }
}
