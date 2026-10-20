<?php
namespace verbb\events\controllers;

use verbb\events\helpers\ElementIndexPermissions;

use craft\elements\db\ElementQueryInterface;

class ElementIndexesController extends \craft\controllers\ElementIndexesController
{
    // Protected Methods
    // =========================================================================

    protected function elementQuery(): ElementQueryInterface
    {
        $query = parent::elementQuery();
        ElementIndexPermissions::restrictQuery($query);

        if (isset($this->unfilteredElementQuery)) {
            ElementIndexPermissions::restrictQuery($this->unfilteredElementQuery);
        }

        return $query;
    }
}
