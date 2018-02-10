<?php

namespace Craft;

/**
 * @property int      id
 * @property int      eventId
 * @property int      ticketId
 * @property int      orderId
 * @property int      lineItemId
 * @property string   ticketSku
 * @property bool     checkedIn
 * @property DateTime checkedInDate
 */
class Events_PurchasedTicketRecord extends BaseRecord
{

    // Public Methods
    // =========================================================================

    public function getTableName()
    {
        return 'events_purchasedtickets';
    }

    public function defineRelations()
    {
        return [
            'event'    => [
                static::BELONGS_TO,
                'Events_EventRecord',
                'required' => false,
                'onDelete' => static::SET_NULL,
            ],
            'ticket'   => [
                static::BELONGS_TO,
                'Events_TicketRecord',
                'required' => false,
                'onDelete' => static::SET_NULL,
            ],
            'order'    => [
                static::BELONGS_TO,
                'Commerce_OrderRecord',
                'required' => false,
                'onDelete' => static::SET_NULL,
            ],
            'lineItem' => [
                static::BELONGS_TO,
                'Commerce_LineItemRecord',
                'required' => false,
                'onDelete' => static::SET_NULL,
            ],
        ];
    }

    // Protected Methods
    // =========================================================================

    protected function defineAttributes()
    {
        return [
            'ticketSku'     => [AttributeType::String],
            'checkedIn'     => [AttributeType::Bool],
            'checkedInDate' => [AttributeType::DateTime],
        ];
    }
}
