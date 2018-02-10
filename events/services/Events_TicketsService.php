<?php
namespace Craft;

class Events_TicketsService extends BaseApplicationComponent
{

    // Properties
    // =========================================================================

    const TICKET_KEY_CHARACTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';


    // Public Methods
    // =========================================================================

    public function getAllTicketsByEventId($eventId, $localeId = null)
    {
//        return craft()->elements->getCriteria('Events_Ticket', array('eventId' => $eventId, 'status' => null, 'locale' => $localeId))->find();
        $records = Events_TicketRecord::model()->findAllByAttributes([
            'eventId' => $eventId,
        ]);

        $models = [];

        foreach($records as $record) {
            $models[] = Events_TicketModel::populateModel($record);
        }

        return $models;
    }

    public function getTicketById($id, $locale = null)
    {
//        $record = Events_TicketRecord ::model()->findById($id);
//        return Events_TicketModel::populateModel($record);
        return craft()->elements->getElementById($id, 'Events_Ticket', $locale);
    }

    public function getTickets($criteria = [])
    {
        if (!$criteria instanceof ElementCriteriaModel) {
            $criteria = craft()->elements->getCriteria('Events_Ticket', $criteria);
        }

        return $criteria->find();
    }

    public function validateTicket(Events_TicketModel $ticket)
    {
        $ticket->clearErrors();

        $record = $this->_getTicketRecord($ticket);
        $this->_populateTicketRecord($record, $ticket);

        $record->validate();
        $ticket->addErrors($record->getErrors());

        if (!craft()->content->validateContent($ticket)) {
            $ticket->addErrors($ticket->getContent()->getErrors());
        }

        // If ticket validation has not already found a clash check all purchasables
        if (!$ticket->getError('sku')) {
            $existing = craft()->commerce_purchasables->getPurchasableBySku($ticket->sku);

            if ($existing) {
                if ($existing->id != $ticket->id) {
                    $ticket->addError('sku', Craft::t('SKU has already been taken by another purchasable.'));
                }
            }
        }

        return !$ticket->hasErrors();
    }

    public function saveTicket(Events_TicketModel $ticket)
    {
        if (!$ticket->id) {
            $record = new Events_TicketRecord();
        } else {
            $record = Events_TicketRecord::model()->findById($ticket->id);

            if (!$record) {
                throw new Exception(Craft::t('No ticket exists with the ID “{id}”', ['id' => $ticket->id]));
            }
        }

        //$record->ownerName = $ticket->ownerName;
        //$record->ownerEmail = $ticket->ownerEmail;

        if (empty($ticket->eventId)) {
            $ticket->addError('eventId', Craft::t('{attribute} cannot be blank.', ['attribute' => 'Event']));
        }

        if (empty($ticket->ticketTypeId)) {
            $ticket->addError('typeId', Craft::t('{attribute} cannot be blank.', ['attribute' => 'TicketType']));
        }

        /*if (empty($ticket->userId) && empty($ticket->ownerEmail)) {
            $ticket->addError('userId', Craft::t('A ticket must have either an email or an owner assigned to it.'));
            $ticket->addError('ownerEmail', Craft::t('A ticket must have either an email or an owner assigned to it.'));
        }

        // Assign ticket to a User if the email matches the User and User field left empty.
        if (
            (craft()->config->get('autoAssignUserOnPurchase', 'events'))
            && empty($ticket->userId) && !empty($ticket->ownerEmail) && $user = craft()->users->getUserByEmail($ticket->ownerEmail)
        ) {
            $ticket->userId = $user->id;
        }*/

        // See if we already have issues with provided data.
        if ($ticket->hasErrors()) {
            return false;
        }

        // If a owner is set, void the name and email fields.
        //if ($ticket->userId) {
            //$record->ownerName = null;
            //$record->ownerEmail = null;
        //}

        //$record->userId = $ticket->userId;

        if (!$record->id) {
            /*do {
                $ticketKey = $this->generateTicketKey();
                $conflict = Events_TicketRecord::model()->findAllByAttributes(['ticketKey' => $ticketKey]);
            } while ($conflict);

            $modifiedTicketKey = craft()->plugins->callFirst('events_modifyTicketKey', [
                $ticketKey,
                $ticket
            ], true);

            // Use the plugin-modified name, if anyone was up to the task.
            $ticketKey = $modifiedTicketKey ?: $ticketKey;

            $record->ticketKey = $ticketKey;*/

//            $event = EventsHelper::getEventsService()->getEventById($ticket->eventId);

            $event = $ticket->getEvent();

            if (!$event) {
                throw new Exception(Craft::t('No event exists with the ID “{id}”', ['id' => $ticket->eventId]));
            }

            $eventType = $event->getEventType();

            if (!$eventType) {
                throw new Exception(Craft::t('No event type exists with the ID “{id}”', ['id' => $event->typeId]));
            }

            $record->eventId = $ticket->eventId;

        } else if ($record->eventId != $ticket->eventId) {
            $ticket->addError('eventId', Craft::t('The ticketd event cannot be changed once a ticket has been created.'));
        }

        $ticketType = $ticket->getTicketType();

        if (!$ticketType) {
            throw new Exception(Craft::t('No ticket type exists with the ID “{id}”', ['id' => $ticketType->id]));
        }

        $record->ticketTypeId = $ticketType->id;

//        $record->sku = $ticket->getSku();
//        $record->price = $ticket->getPrice();
//        $record->quantity = $ticket->quantity;
//        $record->availableFrom = $ticket->availableFrom;
//        $record->availableTo = $ticket->availableTo;
        $record->setAttributes($ticket->getAttributes(), false);

        $record->validate();
        $ticket->addErrors($record->getErrors());

        if ($ticket->hasErrors()) {
            return false;
        }

        $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

        try {
            $event = new Event($this, [
                'ticket' => $ticket,
                'isNewTicket' => !$ticket->id
            ]);
//            $this->onBeforeSaveTicket($event);

            $success = false;

            if ($event->performAction) {
                $success = craft()->elements->saveElement($ticket);
            }

            if (!$success) {
                if ($transaction !== null) {
                    $transaction->rollback();
                }

                return false;
            }

            $record->id = $ticket->id;

            $record->save(false);

            if ($transaction !== null) {
                $transaction->commit();
            }

//            $event = new Event($this, ['ticket' => $ticket]);
//            $this->onSaveTicket($event);
        } catch (\Exception $e) {
            if ($transaction !== null) {
                $transaction->rollback();
            }

            throw $e;
        }

        return true;
    }

    public function setEventOnTickets($event, $tickets)
    {
        foreach ($tickets as $ticket)
        {
            $ticket->setEvent($event);
        }
    }

//    public static function handleCompletedOrder(Event $event)
//    {
//        if (empty($event->params['order'])) {
//            return;
//        }
//
//        $order = $event->params['order'];
//        $lineItems = $order->getLineItems();
//
//        foreach ($lineItems as $lineItem) {
//            $itemId = $lineItem->purchasableId;
//            $element = craft()->elements->getElementById($itemId);
//            $quantity = $lineItem->qty;
//
//            if ($element->getElementType() == "Events_Event") {
//                for ($i = 0; $i < $quantity; $i++) {
//                    craft()->events_tickets->ticketEventByOrder($element, $order);
//                }
//            }
//        }
//    }

//    public static function maybePreventPayment(Event $event)
//    {
//        if (!(craft()->config->get('requireLoggedInUser', 'events') && craft()->userSession->isGuest())) {
//            return;
//        }
//
//        if (empty($event->params['transaction'])) {
//            return;
//        }
//
//        $transaction = $event->params['transaction'];
//        $order = $transaction->order;
//
//        if (!$order) {
//            return;
//        }
//
//        $lineItems = $order->getLineItems();
//
//        foreach ($lineItems as $lineItem) {
//            $itemId = $lineItem->purchasableId;
//            $element = craft()->elements->getElementById($itemId);
//
//            if ($element->getElementType() == "Events_Event") {
//                $transaction->message = Craft::t("You must be logged in to complete this transaction!");
//                $event->performAction = false;
//
//                return;
//            }
//        }
//    }

//    public static function handleUserActivation(Event $event)
//    {
//        if (empty($event->params['user'])) {
//            return;
//        }
//
//        if (!craft()->config->get('autoAssignTicketsOnUserRegistration', 'events')) {
//            return;
//        }
//
//        $user = $event->params['user'];
//        $email = $user->email;
//        $tickets = EventsHelper::getTicketsService()->getTickets(['ownerEmail' => $email]);
//
//        foreach ($tickets as $ticket) {
//            // Only tickets with unassigned users
//            if (!$ticket->userId) {
//                $ticket->userId = $user->id;
//                EventsHelper::getTicketsService()->saveTicket($ticket);
//            }
//        }
//    }

//    public static function handleUserDeletion(Event $event)
//    {
//        if (empty($event->params['user'])) {
//            return;
//        }
//
//        $user = $event->params['user'];
//        $tickets = EventsHelper::getTicketsService()->getTickets(['ownerId' => $user->id, 'eventId' => ':all:']);
//
//        foreach ($tickets as $ticket) {
//            // Transfer the user's tickets to the user's email.
//            $ticket->ownerEmail = $user->email;
//            $ticket->userId = null;
//            EventsHelper::getTicketsService()->saveTicket($ticket);
//        }
//    }

//    public function ticketEventByOrder(Events_EventModel $event, Commerce_OrderModel $order)
//    {
//        $ticket = new Events_TicketModel();
//        $ticket->eventId = $event->id;
//        $customer = $order->getCustomer();
//
//        if ($customer && $user = $customer->getUser()) {
//            $ticket->ownerEmail = $user->email;
//            $ticket->ownerName = $user->getName();
//            $ticket->userId = $user->id;
//        } else {
//            $ticket->ownerEmail = $customer->email;
//        }
//
//        $success = $this->saveTicket($ticket);
//
//        if ($success) {
//            return (bool)craft()->db->createCommand()->update('events_tickets', ['orderId' => $order->id], ['id' => $ticket->id]);
//        }
//
//        return false;
//    }

    public function generateTicketSKU()
    {
        $codeAlphabet = self::TICKET_KEY_CHARACTERS;
        $keyLength = EventsHelper::getPlugin()->getSettings()->ticketSKULength;

        $ticketKey = '';

        for ($i = 0; $i < $keyLength; $i++) {
            $ticketKey .= $codeAlphabet[mt_rand(0, strlen($codeAlphabet) - 1)];
        }

        return $ticketKey;
    }

    public function deleteTicketById($ticketId)
    {
        $ticket = $this->getTicketById($ticketId);

        if ($ticket) {
            return $this->deleteTicket($ticket);
        }

        return false;
    }

    public function deleteTicket($ticket)
    {
        $event = new Event($this, ['ticket' => $ticket]);
//        $this->onBeforeDeleteTicket($event);

        if ($event->performAction && craft()->elements->deleteElementById($ticket->id)) {
//            $event = new Event($this, ['ticket' => $ticket]);
//            $this->onDeleteTicket($event);
            return true;
        }

        return false;
    }


//    public function onBeforeSaveTicket(Event $event)
//    {
//        $this->raiseEvent('onBeforeSaveTicket', $event);
//    }
//
//    public function onSaveTicket(Event $event)
//    {
//        $this->raiseEvent('onSaveTicket', $event);
//    }
//
//    public function onBeforeDeleteTicket(Event $event)
//    {
//        $this->raiseEvent('onBeforeDeleteTicket', $event);
//    }
//
//    public function onDeleteTicket(Event $event)
//    {
//        $this->raiseEvent('onDeleteTicket', $event);
//    }


    // Private Methods
    // =========================================================================

    private function _getTicketRecord(BaseElementModel $model)
    {
        if ($model->id) {
            $record = Events_TicketRecord::model()->findById($model->id);

            if (!$record) {
                throw new HttpException(404);
            }
        } else {
            $record = new Events_TicketRecord();
        }

        return $record;
    }

    private function _populateTicketRecord(Events_TicketRecord $record, Events_TicketModel $model)
    {
//        $record->eventId = $model->eventId;
//        $record->sku = $model->sku;
//
//        $record->price = $model->price;
        $record->setAttributes($model->getAttributes(), false);

        //$record->width = $model->width * 1;
        //$record->height = $model->height * 1;
        //$record->length = $model->length * 1;
        //$//record->weight = $model->weight * 1;
        //$record->minQty = $model->minQty;
        //$record->maxQty = $model->maxQty;
        //$record->stock = $model->stock;
        //$record->isDefault = $model->isDefault;
        //$record->sortOrder = $model->sortOrder;
        //$record->unlimitedStock = $model->unlimitedStock;

        /*if (!$model->getProduct()->getType()->hasDimensions)
        {
            $record->width = $model->width = 0;
            $record->height = $model->height = 0;
            $record->length = $model->length = 0;
            $record->weight = $model->weight = 0;
        }*/

        /*if ($model->unlimitedStock && $record->stock == "")
        {
            $model->stock = 0;
            $record->stock = 0;
        }*/
    }
}
