<?php
namespace verbb\tickets\services;

use verbb\tickets\elements\TicketType;

use Craft;
use craft\base\MemoizableArray;
use craft\helpers\ArrayHelper;

use yii\base\Component;

class TicketTypes extends Component
{
    // Properties
    // =========================================================================

    private ?MemoizableArray $_ticketTypes = null;


    // Public Methods
    // =========================================================================

    public function getAllTicketTypes(): array
    {
        return $this->_ticketTypes()->all();
    }

    public function getAllTicketTypeIds(): array
    {
        return ArrayHelper::getColumn($this->getAllTicketTypes(), 'id', false);
    }

    public function getTicketTypeByHandle(string $handle): ?TicketType
    {
        return $this->_ticketTypes()->firstWhere('handle', $handle, true);
    }

    public function getTicketTypeById(int $id): ?TicketType
    {
        return $this->_ticketTypes()->firstWhere('id', $id);
    }

    public function getTicketTypeByUid(string $uid): ?TicketType
    {
        return $this->_ticketTypes()->firstWhere('uid', $uid, true);
    }

    public function getEditableTicketTypes(): array
    {
        $userSession = Craft::$app->getUser();
        
        return ArrayHelper::where($this->getAllTicketTypes(), function(TicketType $ticketType) use ($userSession) {
            return $userSession->checkPermission("tickets-manageTicketType:$ticketType->id");
        }, true, true, false);
    }

    public function getEditableTicketTypeIds(): array
    {
        return ArrayHelper::getColumn($this->getEditableTicketTypes(), 'id', false);
    }


    // Private methods
    // =========================================================================

    private function _ticketTypes(): MemoizableArray
    {
        if (!isset($this->_ticketTypes)) {
            $ticketTypes = [];

            foreach ($this->_createTicketTypeQuery()->all() as $result) {
                $ticketTypes[] = new TicketType($result);
            }

            $this->_ticketTypes = new MemoizableArray($ticketTypes);
        }

        return $this->_ticketTypes;
    }
}
