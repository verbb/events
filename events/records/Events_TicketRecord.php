<?php

namespace Craft;

/**
 * @property int      id
 * @property int      eventId
 * @property int      ticketTypeId
 * @property string   sku
 * @property int      quantity
 * @property float    price
 * @property DateTime availableFrom
 * @property DateTime availableTo
 */
class Events_TicketRecord extends BaseRecord
{

    // Public Methods
    // =========================================================================

    public function getTableName()
    {
        return 'events_tickets';
    }

    public function defineIndexes()
    {
        return [
            ['columns' => ['sku'], 'unique' => true],
        ];
    }

    public function defineRelations()
    {
        return [
            'element'    => [
                static::BELONGS_TO,
                'ElementRecord',
                'id',
                'required' => true,
                'onDelete' => static::CASCADE
            ],
            'event'      => [
                static::BELONGS_TO,
                'Events_EventRecord',
                'required' => false,
                'onDelete' => static::CASCADE
            ],
            'ticketType' => [
                static::BELONGS_TO,
                'Events_TicketTypeRecord',
                'required' => false,
                'onDelete' => static::CASCADE
            ],
        ];
    }

    // Protected Methods
    // =========================================================================

    protected function defineAttributes()
    {
        return [
            'sku'           => [AttributeType::String, 'required' => true],
            'quantity'      => [AttributeType::Number, 'required' => false],
            'price'         => [AttributeType::Number, 'decimals' => 2, 'required' => false],
            'availableFrom' => [AttributeType::DateTime, 'required' => false],
            'availableTo'   => [AttributeType::DateTime, 'required' => false],
        ];
    }
}
