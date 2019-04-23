<?php
namespace verbb\events\elements\db;

use Craft;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

use yii\db\Connection;

class TicketTypeQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public $id;
    public $taxCategoryId;
    public $shippingCategoryId;
    public $fieldLayoutId;
    public $handle;


    // Protected Methods
    // =========================================================================

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('events_tickettypes');

        $this->query->select([
            'events_tickettypes.id',
            'events_tickettypes.taxCategoryId',
            'events_tickettypes.shippingCategoryId',
            'events_tickettypes.fieldLayoutId',
            'events_tickettypes.handle',
        ]);

        $this->addWhere('id', 'events_tickettypes.id');
        $this->addWhere('taxCategoryId', 'events_tickettypes.taxCategoryId');
        $this->addWhere('shippingCategoryId', 'events_tickettypes.shippingCategoryId');
        $this->addWhere('fieldLayoutId', 'events_tickettypes.fieldLayoutId');
        $this->addWhere('handle', 'events_tickettypes.handle');

        return parent::beforePrepare();
    }


    // Private Methods
    // =========================================================================

    private function addWhere(string $property, string $column)
    {
        if ($this->{$property}) {
            $this->subQuery->andWhere(Db::parseParam($column, $this->{$property}));
        }
    }
}
