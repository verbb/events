<?php
namespace verbb\events\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class TicketTypeQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public mixed $id = null;
    public mixed $taxCategoryId = null;
    public mixed $shippingCategoryId = null;
    public mixed $fieldLayoutId = null;
    public mixed $handle = null;


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

    private function addWhere(string $property, string $column): void
    {
        if ($this->{$property}) {
            $this->subQuery->andWhere(Db::parseParam($column, $this->{$property}));
        }
    }
}
