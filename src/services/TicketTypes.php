<?php
namespace verbb\events\services;

use verbb\events\elements\Event;
use verbb\events\elements\TicketType;

use Craft;
use craft\db\Query;

use yii\base\Component;
use yii\base\Exception;

class TicketTypes extends Component
{
    // Properties
    // =========================================================================

    private $_fetchedAllTicketTypes = false;
    private $_ticketTypesById;
    private $_ticketTypesByHandle;
    private $_allTicketTypeIds;
    private $_editableTicketTypeIds;
    private $_siteSettingsByEventId = [];


    // Public Methods
    // =========================================================================

    public function getEditableTicketTypes(): array
    {
        $editableTicketTypeIds = $this->getEditableTicketTypeIds();
        $editableTicketTypes = [];

        foreach ($this->getAllTicketTypes() as $ticketTypes) {
            if (in_array($ticketTypes->id, $editableTicketTypeIds, false)) {
                $editableTicketTypes[] = $ticketTypes;
            }
        }

        return $editableTicketTypes;
    }

    public function getEditableTicketTypeIds(): array
    {
        if (null === $this->_editableTicketTypeIds) {
            $this->_editableTicketTypeIds = [];
            $allTicketTypeIds = $this->getAllTicketTypeIds();

            foreach ($allTicketTypeIds as $ticketTypeId) {
                if (Craft::$app->getUser()->checkPermission('events-manageTicketType:' . $ticketTypeId)) {
                    $this->_editableTicketTypeIds[] = $ticketTypeId;
                }
            }
        }

        return $this->_editableTicketTypeIds;
    }

    public function getAllTicketTypeIds(): array
    {
        if (null === $this->_allTicketTypeIds) {
            $this->_allTicketTypeIds = [];
            $ticketTypes = $this->getAllTicketTypes();

            foreach ($ticketTypes as $ticketType) {
                $this->_allTicketTypeIds[] = $ticketType->id;
            }
        }

        return $this->_allTicketTypeIds;
    }

    public function getAllTicketTypes(): array
    {
        if (!$this->_fetchedAllTicketTypes) {
            $results = TicketType::find()
                ->orderBy('id')
                ->all();

            foreach ($results as $result) {
                $this->_memoizeTicketType($result);
            }

            $this->_fetchedAllTicketTypes = true;
        }

        return $this->_ticketTypesById ?: [];
    }

    public function getTicketTypeByHandle($handle)
    {
        if (isset($this->_ticketTypesByHandle[$handle])) {
            return $this->_ticketTypesByHandle[$handle];
        }

        if ($this->_fetchedAllTicketTypes) {
            return null;
        }

        $result = TicketType::find()
            ->where(['handle' => $handle])
            ->one();

        if (!$result) {
            return null;
        }

        $this->_memoizeTicketType(new TicketType($result));

        return $this->_ticketTypesByHandle[$handle];
    }

    public function getTicketTypeById(int $ticketTypeId)
    {
        if (isset($this->_ticketTypesById[$ticketTypeId])) {
            return $this->_ticketTypesById[$ticketTypeId];
        }

        if ($this->_fetchedAllTicketTypes) {
            return null;
        }

        $result = TicketType::find()
            ->where(['elements.id' => $ticketTypeId])
            ->one();

        if (!$result) {
            return null;
        }

        $this->_memoizeTicketType($result);

        return $this->_ticketTypesById[$ticketTypeId];
    }


    // Private methods
    // =========================================================================

    private function _memoizeTicketType(TicketType $ticketType)
    {
        $this->_ticketTypesById[$ticketType->id] = $ticketType;
        $this->_ticketTypesByHandle[$ticketType->handle] = $ticketType;
    }
}
