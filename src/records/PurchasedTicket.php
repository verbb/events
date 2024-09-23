<?php
namespace verbb\events\records;

use craft\db\ActiveRecord;
use craft\records\Element;

use craft\commerce\records\Order;
use craft\commerce\records\LineItem;

use yii\db\ActiveQueryInterface;

class PurchasedTicket extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%events_purchased_tickets}}';
    }

    public function getElement(): ActiveQueryInterface
    {
        return self::hasOne(Element::class, ['id' => 'id']);
    }

    public function getEvent(): ActiveQueryInterface
    {
        return self::hasOne(Event::class, ['id' => 'eventId']);
    }

    public function getTicket(): ActiveQueryInterface
    {
        return self::hasOne(Ticket::class, ['id' => 'ticketId']);
    }

    public function getOrder(): ActiveQueryInterface
    {
        return self::hasOne(Order::class, ['id' => 'orderId']);
    }

    public function getLineItem(): ActiveQueryInterface
    {
        return self::hasOne(LineItem::class, ['id' => 'lineItemId']);
    }
}