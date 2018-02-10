<?php

namespace Craft;

class Events_EventsService extends BaseApplicationComponent
{
    // Public Methods
    // =========================================================================

    public function getEventById($id, $localeId = null)
    {
        return craft()->elements->getElementById($id, 'Events_Event', $localeId);
    }

    public function getEvents(array $criteria = [])
    {
        if (!$criteria instanceof ElementCriteriaModel) {
            $criteria = craft()->elements->getCriteria('Events_Event', $criteria);
        }

        return $criteria->find();
    }

    /**
     * @param Events_EventModel $eventModel
     * @param array             $data
     */
    public function populateEventTicketModels(Events_EventModel $eventModel, array $data)
    {
        $ticketData = $data;
        $tickets = [];
        $count = 1;

        if (empty($ticketData)) {
            $ticketData = [];
        }

        $productId = $eventModel->id;

        foreach ($ticketData as $key => $ticket) {
            if ($productId && strncmp($key, 'new', 3) !== 0) {
                $ticketModel = EventsHelper::getTicketsService()->getTicketById($key, $eventModel->locale);
            } else {
                $ticketModel = new Events_TicketModel();
            }

            $ticketModel->setEvent($eventModel);
            $ticketModel->enabled = isset($ticket['enabled']) ? $ticket['enabled'] : 1;
            $ticketModel->price = LocalizationHelper::normalizeNumber($ticket['price']);
            $ticketModel->quantity = isset($ticket['quantity']) ? $ticket['quantity'] : 0;
            $ticketModel->sku = ($ticketModel->sku !== null) ? $ticketModel->sku : EventsHelper::getTicketsService()->generateTicketSKU();
            $ticketModel->availableFrom = isset($ticket['availableFrom']) ? $ticket['availableFrom'] : null;
            $ticketModel->availableTo = isset($ticket['availableTo']) ? $ticket['availableTo'] : null;

            if (isset($ticket['ticketTypes']) && is_array($ticket['ticketTypes'])) {

                $ticketTypeId = $ticket['ticketTypes'][0];
                $ticketTypeModel = EventsHelper::getTicketTypesService()->getTicketTypeById($ticketTypeId);

                $ticketModel->setTicketType($ticketTypeModel);
            }

            $ticketModel->sortOrder = $count++;

            if (isset($ticket['fields'])) {
                $ticketModel->setContentFromPost($ticket['fields']);
            }

            if (isset($ticket['title'])) {
                $ticketModel->getContent()->title = $ticket['title'];
            }

            $tickets[] = $ticketModel;
        }

        $eventModel->setTickets($tickets);
    }

    public function saveEvent(Events_EventModel $model)
    {
        $isNewEvent = !$model->id;

        if (!$model->id) {
            $record = new Events_EventRecord();
        } else {
            $record = Events_EventRecord::model()->findById($model->id);

            if (!$record) {
                throw new Exception(Craft::t('No event exists with the ID “{id}”', ['id' => $model->id]));
            }
        }

        $record->setAttributes($model->getAttributes(), false);

        $record->validate();
        $model->addErrors($record->getErrors());

        $eventType = EventsHelper::getEventTypesService()->getEventTypeById($model->typeId);

        if (!$eventType) {
            throw new Exception(Craft::t('No event type exists with the ID “{id}”', ['id' => $model->typeId]));
        }

        // Validate our tickets attached to this event
        $ticketsValid = true;
        foreach ($model->getTickets() as $ticket) {
            // If we have a blank SKU, generate from eventtype's skuFormat
//            if (!$ticket->sku){
//                try {
////                    $ticket->sku = craft()->templates->renderObjectTemplate($eventType->skuFormat, $model);
//                    $ticket->sku = craft()->templates->renderObjectTemplate($eventType->skuFormat, $model);
//                } catch(\Exception $e){
//                    $ticket->sku = "";
//                }
//            }
            if (!EventsHelper::getTicketsService()->validateTicket($ticket)) {
                $ticketsValid = false;

                if ($ticket->getError('title')) {
                    $model->addError('title', Craft::t('Title cannot be blank.'));
                }
            }
        }

        if ($model->hasErrors() || !$ticketsValid) {
            return false;
        }

        $event = new Event($this, ['event' => $model, 'isNewEvent' => $isNewEvent]);
        $this->onBeforeSaveEvent($event);

        $transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

        try {
            $success = false;

            if ($event->performAction) {
                $success = craft()->elements->saveElement($model);
            }

            if ($success) {
                // Now that we have an element ID, save it on the other stuff
                if ($isNewEvent) {
                    $record->id = $model->id;
                }

                $record->save(false);

                $keepTicketIds = [];
                $oldTicketIds = craft()->db->createCommand()
                    ->select('id')
                    ->from('events_tickets')
                    ->where('eventId = :eventId', [':eventId' => $model->id])
                    ->queryColumn();

                foreach ($model->getTickets() as $ticket) {
                    $ticket->setEvent($model);

                    $success = EventsHelper::getTicketsService()->saveTicket($ticket);

                    $keepTicketIds[] = $ticket->id;
                }

                foreach (array_diff($oldTicketIds, $keepTicketIds) as $deleteId) {
                    EventsHelper::getTicketsService()->deleteTicketById($deleteId);
                }
            } else {
                $success = false;
            }

            if (!$success) {
                if ($transaction !== null) {
                    $transaction->rollback();
                }

                return false;
            }

            if ($transaction !== null) {
                $transaction->commit();
            }

            $event = new Event($this, ['event' => $model, 'isNewEvent' => $isNewEvent]);
            $this->onSaveEvent($event);
        } catch (\Exception $e) {
            if ($transaction !== null) {
                $transaction->rollback();
            }

            throw $e;
        }

        return true;
    }

    public function deleteEvent(Events_EventModel $model)
    {
        $event = new Event($this, ['event' => $model]);
        $this->onBeforeDeleteEvent($event);

        if ($event->performAction && craft()->elements->deleteElementById($model->id)) {
            $event = new Event($this, ['event' => $model]);
            $this->onDeleteEvent($event);

            return true;
        }

        return false;
    }


    // Events
    // =========================================================================

    public function onBeforeDeleteEvent(Event $event)
    {
        $this->raiseEvent('onBeforeDeleteEvent', $event);
    }

    public function onDeleteEvent(Event $event)
    {
        $this->raiseEvent('onDeleteEvent', $event);
    }

    public function onBeforeSaveEvent(Event $event)
    {
        $this->raiseEvent('onBeforeSaveEvent', $event);
    }

    public function onSaveEvent(Event $event)
    {
        $this->raiseEvent('onSaveEvent', $event);
    }
}
