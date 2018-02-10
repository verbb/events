<?php

namespace Craft;

use Endroid\QrCode\QrCode;

/**
 * @property int      id
 * @property int      eventId
 * @property int      ticketId
 * @property int      orderId
 * @property int      lineItemId
 * @property string   ticketSku
 * @property bool     checkedIn
 * @property DateTime checkedInDate
 * @property DateTime dateCreated
 */
class Events_PurchasedTicketModel extends BaseModel
{
    // Properties
    // =========================================================================

    private $_event;
    private $_ticket;
    private $_order;
    private $_lineItem;


    // Public Methods
    // =============================================================================

    /**
     * @return null|string
     */
    public function __toString()
    {
        return (string)$this->ticketSku;
    }

    /**
     * @return Events_EventModel
     */
    public function getEvent()
    {
        if ($this->_event) {
            return $this->_event;
        }

        return $this->_event = EventsHelper::getEventsService()->getEventById($this->eventId);
    }

    /**
     * @return Events_TicketModel
     */
    public function getTicket()
    {
        if ($this->_ticket) {
            return $this->_ticket;
        }

        return $this->_ticket = EventsHelper::getTicketsService()->getTicketById($this->ticketId);
    }

    /**
     * @return bool|Commerce_OrderModel
     */
    public function getOrder()
    {
        if ($this->_order) {
            return $this->_order;
        }

        if ($this->orderId) {
            return $this->_order = craft()->commerce_orders->getOrderById($this->orderId);
        }

        return false;
    }

    /**
     * @return bool|Commerce_OrderModel
     */
    public function getLineItem()
    {
        if ($this->_lineItem) {
            return $this->_lineItem;
        }

        if ($this->lineItemId) {
            return $this->_lineItem = craft()->commerce_lineItems->getLineItemById($this->lineItemId);
        }

        return false;
    }

    /**
     * @return Events_EventTypeModel
     */
    public function getEventType()
    {
        $event = $this->getEvent();

        if ($event) {
            return $event->getEventType();
        }

        return null;
    }

    /**
     * @return Events_TicketTypeModel
     */
    public function getTicketType()
    {
        $ticket = $this->getTicket();

        if ($ticket) {
            return $ticket->getTicketType();
        }

        return null;
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return $this->getEvent()->getTitle();
    }

    /**
     * @return string
     */
    public function getTicketName()
    {
        return $this->getTicketType()->getTitle();
    }

    public function getQR()
    {
        $url = UrlHelper::getActionUrl('events/ticket/checkin', ['sku' => $this->ticketSku]);

        $qrCode = new QrCode();
        $qrCode
            ->setText($url)
            ->setSize(300)
            ->setErrorCorrection('high')
            ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0])
            ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);

        return $qrCode->getDataUri();
    }

    // Protected Methods
    // =============================================================================

    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), [
            'id'            => [AttributeType::Number],
            'eventId'       => [AttributeType::Number],
            'ticketId'      => [AttributeType::Number],
            'orderId'       => [AttributeType::Number],
            'lineItemId'    => [AttributeType::Number],
            'ticketSku'     => [AttributeType::String],
            'checkedIn'     => [AttributeType::Bool],
            'checkedInDate' => [AttributeType::DateTime],
            'dateCreated'   => [AttributeType::DateTime],
        ]);
    }

}
