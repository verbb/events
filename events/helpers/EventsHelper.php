<?php

namespace Craft;

/**
 * Class EventsHelper
 * Creates helper functions for plugin/services to provide code completion
 *
 * @package Craft
 */
class EventsHelper
{
    // Public Methods
    // =========================================================================

    /**
     * @return EventsPlugin
     */
    public static function getPlugin()
    {
        return craft()->plugins->getPlugin('events');
    }

    /**
     * @return Events_PluginService
     */
    public static function getPluginService()
    {
        return craft()->events_plugin;
    }

    /**
     * @return Events_LicenseService
     */
    public static function getLicenseService()
    {
        return craft()->events_license;
    }

    /**
     * @return Events_EventsService
     */
    public static function getEventsService()
    {
        return craft()->events_events;
    }

    /**
     * @return Events_EventTypesService
     */
    public static function getEventTypesService()
    {
        return craft()->events_eventTypes;
    }

    /**
     * @return Events_TicketsService
     */
    public static function getTicketsService()
    {
        return craft()->events_tickets;
    }

    /**
    * @return Events_TicketTypesService
    */
    public static function getTicketTypesService()
    {
        return craft()->events_ticketTypes;
    }

    /**
     * @return Events_PurchasedTicketsService
     */
    public static function getPurchasedTicketsService()
    {
        return craft()->events_purchasedTickets;
    }

    /**
     * @return Events_pdfService
     */
    public static function getPdfService()
    {
        return craft()->events_pdf;
    }
}