<?php
namespace verbb\events\elements\db;

use verbb\events\helpers\ElementIndexPermissions;

trait ElementIndexQueryTrait
{
    // Properties
    // =========================================================================

    private ?array $_elementIndexScope = null;


    // Public Methods
    // =========================================================================

    public function restrictElementIndex(array $scope): void
    {
        $this->_elementIndexScope = $scope;
        $this->clearCachedResult();
    }

    public function applyElementIndexScope(): void
    {
        if ($this->_elementIndexScope !== null) {
            ElementIndexPermissions::applyScope($this, $this->_elementIndexScope);
        }
    }
}
