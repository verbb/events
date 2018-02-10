<?php

namespace Craft;

/**
 * @property int    id
 * @property int    taxCategoryId
 * @property int    shippingCategoryId
 * @property int    fieldLayoutId
 * @property string handle
 */
class Events_TicketTypeRecord extends BaseRecord
{

    // Public Methods
    // =========================================================================

    public function getTableName()
    {
        return 'events_tickettypes';
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
            'element'     => [
                static::BELONGS_TO,
                'ElementRecord',
                'id',
                'required' => true,
                'onDelete' => static::CASCADE,
            ],
            'taxCategory' => [
                static::BELONGS_TO,
                'Commerce_TaxCategoryRecord',
                'required' => true,
            ],
            'shippingCategory' => [
                static::BELONGS_TO,
                'Commerce_ShippingCategoryRecord',
                'required' => true,
            ],
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
//            'title' => [AttributeType::Name, 'required' => true],
            'handle'    => [AttributeType::Handle, 'required' => true],
//            'hasUrls'   => AttributeType::Bool,
//            'urlFormat' => AttributeType::String,
//            'skuFormat' => AttributeType::String,
//            'template'  => AttributeType::Template,
        ];
    }
}
