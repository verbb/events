<?php
namespace verbb\events\models;

use verbb\events\Events;

use Craft;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

use craft\commerce\Plugin as Commerce;

use Endroid\QrCode\QrCode;

class PurchasedTicket extends Model
{
    // Properties
    // =========================================================================

    public $id;
    public $eventId;
    public $ticketId;
    public $orderId;
    public $lineItemId;
    public $ticketSku;
    public $checkedIn;
    public $checkedInDate;
    public $dateCreated;
    public $dateUpdated;
    public $uid;

    private $_event;
    private $_ticket;
    private $_order;
    private $_lineItem;


    // Public Methods
    // =============================================================================

    public function __toString()
    {
        return (string)$this->ticketSku;
    }

    public function getEvent()
    {
        if ($this->_event) {
            return $this->_event;
        }

        return $this->_event = Events::$plugin->getEvents()->getEventById($this->eventId);
    }

    public function getTicket()
    {
        if ($this->_ticket) {
            return $this->_ticket;
        }

        return $this->_ticket = Events::$plugin->getTickets()->getTicketById($this->ticketId);
    }

    public function getOrder()
    {
        if ($this->_order) {
            return $this->_order;
        }

        if ($this->orderId) {
            return $this->_order = Commerce::getInstance()->getOrders()->getOrderById($this->orderId);
        }

        return false;
    }

    public function getLineItem()
    {
        if ($this->_lineItem) {
            return $this->_lineItem;
        }

        if ($this->lineItemId) {
            return $this->_lineItem = Commerce::getInstance()->getLineItems()->getLineItemById($this->lineItemId);
        }

        return false;
    }

    public function getEventType()
    {
        $event = $this->getEvent();

        if ($event) {
            return $event->getEventType();
        }

        return null;
    }

    public function getTicketType()
    {
        $ticket = $this->getTicket();

        if ($ticket) {
            return $ticket->getTicketType();
        }

        return null;
    }

    public function getEventName()
    {
        return $this->getEvent()->getTitle();
    }

    public function getTicketName()
    {
        return $this->getTicket()->getName();
    }

    public function getQrCode()
    {
        $url = UrlHelper::actionUrl('events/ticket/checkin', ['sku' => $this->ticketSku]);

        $qrCode = new QrCode();

        $qrCode
            ->setText($url)
            ->setSize(300)
            ->setErrorCorrection('high')
            ->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0])
            ->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);

        return $qrCode->getDataUri();
    }

}
