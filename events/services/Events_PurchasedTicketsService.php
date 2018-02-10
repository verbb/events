<?php

namespace Craft;

class Events_PurchasedTicketsService extends BaseApplicationComponent
{

    // Properties
    // =========================================================================

    const TICKET_KEY_CHARACTERS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';


    // Public Methods
    // =========================================================================

    /**
     * @param array $attributes
     * @param array $options
     *
     * @return Events_PurchasedTicketModel
     */
    public function getByAttributes($attributes = array(), $options = array())
    {
        $record = Events_PurchasedTicketRecord::model()->findByAttributes($attributes, $options);
        return Events_PurchasedTicketModel::populateModel($record);
    }

    /**
     * @param array $attributes
     * @param array $options
     *
     * @return Events_PurchasedTicketModel[]
     */
    public function getAllByAttributes(array $attributes = array(), array $options = array())
    {
        $records = Events_PurchasedTicketRecord::model()->findAllByAttributes($attributes, $options);
        return Events_PurchasedTicketModel::populateModels($records);
    }

    /**
     * Generate a unique ticket sku by the TICKET_KEY_CHARACTERS and the
     * ticketSKULength settings
     *
     * @return string
     */
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

    /**
     * Craft Commerce onPopulateLineItem handler to ensure users cannot change
     * ticket quantity to more than available.
     *
     * @param Event $event
     */
    public function onPopulateLineItemHandler(Event $event)
    {
        $purchasable = $event->params['purchasable'];
        $lineItem = $event->params['lineItem'];
        $errors = array();

        if($purchasable instanceof Events_TicketModel){

            $purchasedTickets = EventsHelper::getPurchasedTicketsService()->getAllByAttributes(['ticketId' => $purchasable->id]);
            $availableTickets = $purchasable->quantity - count($purchasedTickets);

            if ($lineItem->qty > $availableTickets) {
                $lineItem->qty = $availableTickets;
                $errors[] = 'You reached the maximum ticket quantity for ' . $purchasable->getDescription();
            }
        }

        if ($errors) {

            $cart = craft()->commerce_cart->getCart();
            $cart->addErrors($errors);

            craft()->userSession->setError(implode(',', $errors));
        }
    }

    /**
     * Craft Commerce onOrderComplete handler to generate a completed,
     * purchased ticket item.
     *
     * @param Event $event
     */
    public function onOrderCompleteHandler(Event $event)
    {
        /** @var Commerce_OrderModel $order */
        $order = $event->params['order'];

        foreach ($order->getLineItems() as $lineItem) {

            /** @var Events_TicketModel $element */
            $element = craft()->elements->getElementById($lineItem->purchasableId);
            $quantity = $lineItem->qty;

            if ($element instanceof Events_TicketModel) {

                // Make sure we create a purchased ticket for each ticket and item in the order
                for ($i = 0; $i < $quantity; $i++) {

                    $record = new Events_PurchasedTicketRecord();
                    $record->eventId = $element->eventId;
                    $record->ticketId = $element->id;
                    $record->orderId = $order->id;
                    $record->lineItemId = $lineItem->id;
                    $record->ticketSku = EventsHelper::getPurchasedTicketsService()->generateTicketSKU();

                    $record->save(false);
                }
            }
        }
    }
}