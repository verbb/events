<?php

namespace Craft;

use Commerce\Interfaces\Purchasable;

/**
 * @property int      id
 * @property int      eventId
 * @property int      ticketTypeId
 * @property string   sku
 * @property int      quantity
 * @property float    price
 * @property DateTime availableFrom
 * @property DateTime availableTo
 *
 * @property int      sortOrder
 * @property int      orderId
 * @property DateTime dateCreated
 * @property DateTime dateUpdated
 */
class Events_TicketModel extends BaseElementModel implements Purchasable
{
    // Properties
    // =========================================================================

    private $_event;
    private $_order;
    private $_ticketType;
    protected $elementType = 'Events_Ticket';


    // Public Methods
    // =========================================================================

    public function __toString()
    {
        return $this->getTicketType()->getTitle();
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
     * @return Events_TicketTypeModel
     */
    public function getTicketType()
    {
        if ($this->_ticketType) {
            return $this->_ticketType;
        }

        return $this->_ticketType = EventsHelper::getTicketTypesService()->getTicketTypeById($this->ticketTypeId);
    }

    /**
     * @return Events_TicketTypeModel[]
     */
    public function getTicketTypes()
    {
        return [
            $this->getTicketType(),
        ];
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
     * Returns the ticket's event type model. Alias of ::getEventType()
     *
     * @return Events_EventTypeModel
     */
    public function getType()
    {
        return $this->getEventType();
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return (string)$this->getEvent();
    }

    /**
     * @return string
     */
    public function getCpEditUrl()
    {
        return UrlHelper::getCpUrl('events/tickets/' . $this->id);
    }

    /**
     * @return string
     */
    public function getOrderEditUrl()
    {
        if ($this->orderId) {
            return UrlHelper::getCpUrl('commerce/orders/' . $this->orderId);
        }

        return '';
    }

    public function getFieldLayout()
    {
        $ticketType = $this->getTicketType();

        if ($ticketType) {
            return $ticketType->asa('ticketFieldLayout')->getFieldLayout();
        }

        return null;
    }

    public function setEvent(Events_EventModel $event = null)
    {
        $this->_event = $event;

        if ($event !== null) {
            $this->locale = $event->locale;

            if ($event->id) {
                $this->eventId = $event->id;
            }
        }
    }

    public function setTicketType(Events_TicketTypeModel $ticketType = null)
    {
        $this->_ticketType = $ticketType;

        if ($ticketType !== null && $ticketType->id) {
            $this->ticketTypeId = $ticketType->id;
        }
    }

    /**
     * Get the purchased tickets for a given line item
     *
     * @param Commerce_LineItemModel $lineItem
     *
     * @return Events_PurchasedTicketModel[]
     */
    public function getPurchasedTicketsForLineItem(Commerce_LineItemModel $lineItem)
    {
        return EventsHelper::getPurchasedTicketsService()->getAllByAttributes([
            'orderId' => $lineItem->order->id,
            'lineItemId' => $lineItem->id
        ]);
    }

    /**
     * Return Ticket as the product
     *
     * @return Events_TicketModel
     */
    public function getProduct()
    {
        return $this;
    }


    // Implement Purchasable
    // =========================================================================

    public function getPurchasableId()
    {
        return $this->id;
    }

    public function getSnapshot()
    {
        return $this->getAttributes();
    }

    public function getPrice()
    {
        return $this->getAttribute('price');
    }

    public function getSku()
    {
        return $this->getAttribute('sku');
    }

    public function getDescription()
    {
        return $this->getEventName() . ' - ' . $this->getTicketType()->getTitle();
    }

    public function getTaxCategoryId()
    {
        return $this->getTicketType()->taxCategoryId;
    }

    public function getShippingCategoryId()
    {
        return $this->getTicketType()->shippingCategoryId;
    }

    public function getIsAvailable()
    {
        return true;
    }

    public function populateLineItem(Commerce_LineItemModel $lineItem)
    {
        return null;
    }

    public function validateLineItem(Commerce_LineItemModel $lineItem)
    {
        return true;
    }

    public function hasFreeShipping()
    {
        return true;
    }

    public function getIsPromotable()
    {
        return true;
    }



    // Protected Methods
    // =========================================================================

    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), [
            'id'            => AttributeType::Number,
            'eventId'       => AttributeType::Number,
            //'ticketKey' => AttributeType::String,
            //'ownerName' => AttributeType::String,
            //'ownerEmail' => AttributeType::String,
            //'userId' => AttributeType::Number,
            'ticketTypeId'  => AttributeType::Number,
            'sku'           => AttributeType::String,
            'quantity'      => AttributeType::Number,
            'price'         => [AttributeType::Number, 'decimals' => 2],
            'availableFrom' => AttributeType::DateTime,
            'availableTo'   => AttributeType::DateTime,

            'sortOrder'   => AttributeType::Number,
            'orderId'     => AttributeType::Number,
            'dateCreated' => AttributeType::DateTime,
            'dateUpdated' => AttributeType::DateTime,
//            'taxCategoryId' => AttributeType::Number,
        ]);
    }
}
