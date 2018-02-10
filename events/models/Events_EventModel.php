<?php
namespace Craft;

/**
 * @property int      id
 * @property int      typeId
 * @property bool     allDay
 * @property int      capacity
 * @property DateTime startDate
 * @property DateTime endDate
 */
class Events_EventModel extends BaseElementModel
{
    const LIVE = 'live';
//    const PENDING = 'pending';
    const EXPIRED = 'expired';

    // Properties
    // =========================================================================

    protected $elementType = 'Events_Event';
    private $_eventType;
    private $_tickets;

    // Public Methods
    // =============================================================================

    public function __toString()
    {
        return (string)Craft::t($this->title);
    }

    public function getStatus()
    {
        $status = parent::getStatus();

        if ($status == static::ENABLED && $this->startDate) {
            $currentTime = DateTimeHelper::currentTimeStamp();
            $startDate = $this->startDate->getTimestamp();
            $endDate = ($this->endDate ? $this->endDate->getTimestamp() : null);

            if ( (!$endDate && $startDate <= $currentTime) || ($endDate && $endDate <= $currentTime)) {
                return static::EXPIRED;
            }

            return static::LIVE;
        }

        return $status;
    }

    public function isEditable()
    {
        if ($this->getEventType() && EventsHelper::getLicenseService()->isLicensed()) {
            $id = $this->getEventType()->id;

            return craft()->userSession->checkPermission('events-manageEventType:'.$id);
        }

        return false;
    }

    public function isLocalized()
    {
        return true;
    }

    public function getCpEditUrl()
    {
        $eventType = $this->getEventType();

        if ($eventType) {
            return UrlHelper::getCpUrl('events/events/'.$eventType->handle.'/'.$this->id);
        }

        return null;
    }

    public function getFieldLayout()
    {
        $eventType = $this->getEventType();

        if ($eventType) {
            return $eventType->asa('eventFieldLayout')->getFieldLayout();
        }

        return null;
    }

    public function getUrlFormat()
    {
        $eventType = $this->getEventType();

        if ($eventType && $eventType->hasUrls) {
            $eventTypeLocales = $eventType->getLocales();

            if (isset($eventTypeLocales[$this->locale])) {
                return $eventTypeLocales[$this->locale]->urlFormat;
            }
        }

        return '';
    }

    /**
     * Returns the voucher's voucher type model.
     *
     * @return Events_EventTypeModel|null
     */
    public function getEventType()
    {
        if ($this->_eventType) {
            return $this->_eventType;
        }

        return $this->_eventType = EventsHelper::getEventTypesService()->getEventTypeById($this->typeId);
    }

    /**
     * @return Events_TicketModel[]
     */
    public function getTickets()
    {
        if (empty($this->_tickets)) {
            if ($this->id) {
                $this->setTickets(EventsHelper::getTicketsService()->getAllTicketsByEventId($this->id, $this->locale));
            }

            // Must have at least one
            if (empty($this->_tickets)) {
                $ticket = new Events_TicketModel();
                $this->setTickets([$ticket]);
            }
        }

        return $this->_tickets;
    }

    public function setTickets($tickets)
    {
        EventsHelper::getTicketsService()->setEventOnTickets($this, $tickets);
        $this->_tickets = $tickets;
    }

    /*public function getOwner()
    {
        if (!isset($this->_owner) && $this->ownerId) {
            $this->_owner = craft()->elements->getElementById($this->ownerId);

            if (!$this->_owner) {
                $this->_owner = false;
            }
        }

        if ($this->_owner) {
            return $this->_owner;
        }
    }

    public function setOwner(BaseElementModel $owner)
    {
        $this->_owner = $owner;
    }

    public function getSessions()
    {
        if (!isset($this->_sessions) && $this->id) {
            $this->_sessions = craft()->events_session->getAllByAttributes(array('eventId' => $this->id));

            if (!$this->_sessions) {
                $this->_sessions = false;
            }
        }

        if ($this->_sessions) {
            return $this->_sessions;
        }
    }

    public function setSessions($sessions)
    {
        $this->_sessions = $sessions;
    }

    public function getAvailableStartDate()
    {
        $date = $this->getAttribute('availableStartDate');

        // Check for null DateTime
        if ($date) {
            if ($date->year() == '-0001') {
                return null;
            } else {
                return $date;
            }
        }
    }

    public function getAvailableEndDate()
    {
        $date = $this->getAttribute('availableEndDate');

        // Check for null DateTime
        if ($date) {
            if ($date->year() == '-0001') {
                return null;
            } else {
                return $date;
            }
        }
    }*/



    /*public function setSessions($sessions) {
        $this->sessions = $sessions;
    }

    public function setTickets($tickets) {
        $this->tickets = $tickets;
    }*/

    /*public function getEventId()
    {
        return $this->id;
    }

    public function getTickets()
    {
        return EventsHelper::getTicketsService()->getAllByAttributes(array('eventId' => $this->eventId));
    }

    public function getProduct()
    {
        $variant = craft()->market_variant->getAllByProductId($this->productId, true);

        if ($variant) {
            return $variant[0];
        }
    }

    public function getTicketStock()
    {
        $variant = craft()->market_variant->getAllByProductId($this->productId, true);

        if ($variant) {
            return $variant[0]->stock;
        }
    }*/


    // Protected Methods
    // =============================================================================

    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), array(
            //'id'            => AttributeType::Number,
            //'handle'        => AttributeType::String,
            'typeId'        => AttributeType::Number,
            'allDay'        => AttributeType::Bool,
            'capacity'      => AttributeType::Number,
            'startDate'     => AttributeType::DateTime,
            'endDate'       => AttributeType::DateTime,
        ));
    }

}