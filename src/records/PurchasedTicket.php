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
        return '{{%events_purchasedtickets}}';
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }

    public function getEvent(): ActiveQueryInterface
    {
        return $this->hasOne(Event::class, ['id' => 'eventId']);
    }

    public function getTicket(): ActiveQueryInterface
    {
        return $this->hasOne(Ticket::class, ['id' => 'ticketId']);
    }

    public function getOrder(): ActiveQueryInterface
    {
        return $this->hasOne(Order::class, ['id' => 'orderId']);
    }

    public function getLineItem(): ActiveQueryInterface
    {
        return $this->hasOne(LineItem::class, ['id' => 'lineItemId']);
    }
}