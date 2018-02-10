<?php
namespace Craft;

class EventsVariable
{
    /**
     * @return EventsPlugin
     */
    public function getPlugin()
    {
        return EventsHelper::getPlugin();
    }

    /**
     * @return string
     */
    public function getPluginUrl()
    {
        return $this->getPlugin()->getPluginUrl();
    }

    /**
     * @return string
     */
    public function getPluginName()
    {
        return $this->getPlugin()->getName();
    }

    /**
     * @return string
     */
    public function getPluginVersion()
    {
        return $this->getPlugin()->getVersion();
    }

    /**
     * @return bool
     */
    public function isLicensed()
    {
        return EventsHelper::getLicenseService()->isLicensed();
    }

    /**
     * @return mixed
     */
    public function getEdition()
    {
        return EventsHelper::getLicenseService()->getEdition();
    }

    /**
     * @return Events_EventTypeModel[]
     */
    public function getAllEventTypes()
    {
        return EventsHelper::getEventTypesService()->getEventTypes();
    }

    /**
     * @return Events_TicketTypeModel[]
     */
    public function getAllTicketTypes()
    {
        return EventsHelper::getTicketTypesService()->getTicketTypes();
    }

    /**
     * @param array|ElementCriteriaModel $criteria
     *
     * @return ElementCriteriaModel
     * @throws Exception
     */
    public function tickets(array $criteria = [])
    {
        return craft()->elements->getCriteria('Events_Ticket', $criteria);
    }

    /**
     * @param array|ElementCriteriaModel $criteria
     *
     * @return ElementCriteriaModel
     * @throws Exception
     */
    public function events(array $criteria = [])
    {
        return craft()->elements->getCriteria('Events_Event', $criteria);
    }

    /**
     * @param array|ElementCriteriaModel $criteria
     *
     * @return ElementCriteriaModel
     * @throws Exception
     */
    public function ticketTypes(array $criteria = [])
    {
        return craft()->elements->getCriteria('Events_TicketType', $criteria);
    }

    /**
     * @param array $attributes
     * @param array $options
     *
     * @return Events_PurchasedTicketModel[]
     */
    public function purchasedTickets(array $attributes = array(), array $options = array())
    {
        return EventsHelper::getPurchasedTicketsService()->getAllByAttributes($attributes, $options);
    }

    /**
     * @param int $eventId
     *
     * @return ElementCriteriaModel
     * @throws Exception
     */
    public function availableTickets($eventId)
    {
        $currentTimeDb = DateTimeHelper::currentTimeForDb();

        $criteria = array(
            'eventId' => $eventId,
            'availableFrom' => $currentTimeDb,
            'availableTo' => $currentTimeDb,
        );

        return craft()->elements->getCriteria('Events_Ticket', $criteria);
    }

    /**
     * Get the URL to the PDF file for all tickets of that order.
     *
     * @param string $orderNumber
     *
     * @return false|string
     * @throws Exception
     */
    public function getOrderPdfUrl($orderNumber)
    {
        return EventsHelper::getPdfService()->getPdfUrl($orderNumber);
    }

    /**
     * Get the URL to the PDF file for the tickets of that line item.
     *
     * @param Commerce_LineItemModel $lineItem
     *
     * @return null|string
     * @throws Exception
     */
    public function getPdfUrl(Commerce_LineItemModel $lineItem)
    {
        if ($this->isTicket($lineItem)) {
            $orderNumber = $lineItem->order->number;

            return EventsHelper::getPdfService()->getPdfUrl($orderNumber, $lineItem->id);
        }

        return null;
    }

    /**
     * Checks if the line item is a ticket
     *
     * @param $lineItem
     *
     * @return bool
     */
    public function isTicket($lineItem)
    {
        return ($lineItem->purchasable->elementType == 'Events_Ticket');
    }
}
