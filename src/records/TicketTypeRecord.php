<?php
namespace verbb\events\records;

use craft\db\ActiveRecord;
use craft\records\Element;
use craft\records\FieldLayout;

use craft\commerce\records\TaxCategory;
use craft\commerce\records\ShippingCategory;

use yii\db\ActiveQueryInterface;

class TicketTypeRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    public static function tableName(): string
    {
        return '{{%events_tickettypes}}';
    }

    public function getFieldLayout(): ActiveQueryInterface
    {
        return $this->hasOne(FieldLayout::class, ['id' => 'fieldLayoutId']);
    }

    public function getTaxCategory(): ActiveQueryInterface
    {
        return $this->hasOne(TaxCategory::class, ['id' => 'taxCategoryId']);
    }

    public function getShippingCategory(): ActiveQueryInterface
    {
        return $this->hasOne(ShippingCategory::class, ['id' => 'shippingCategoryId']);
    }

    public function getElement(): ActiveQueryInterface
    {
        return $this->hasOne(Element::class, ['id' => 'id']);
    }
}
