<?php

namespace Craft;

/**
 * @property int    id
 * @property int    fieldLayoutId
 * @property string name
 * @property string handle
 * @property bool   hasUrls
 * @property bool   urlFormat
 * @property string template
 */
class Events_EventTypeRecord extends BaseRecord
{

    // Public Methods
    // =========================================================================

    public function getTableName()
    {
        return 'events_eventtypes';
    }

    public function defineIndexes()
    {
        return [
            ['columns' => ['handle'], 'unique' => true],
        ];
    }

    public function defineRelations()
    {
        return [
            'fieldLayout' => [
                static::BELONGS_TO,
                'FieldLayoutRecord',
                'onDelete' => static::SET_NULL,
            ],
        ];
    }

    // Protected Methods
    // =========================================================================

    protected function defineAttributes()
    {
        return [
            'name'          => [AttributeType::Name, 'required' => true],
            'handle'        => [AttributeType::Handle, 'required' => true],
//            'taxCategoryId' => AttributeType::Number,
            'hasUrls'       => AttributeType::Bool,
            'urlFormat'     => AttributeType::String,
//            'skuFormat' => AttributeType::String,
            'template'      => AttributeType::Template,
        ];
    }
}
