<?php
namespace verbb\events\base;

use verbb\events\Events;
use verbb\events\services\Events as EventsService;
use verbb\events\services\EventTypes;
use verbb\events\services\Pdf;
use verbb\events\services\PurchasedTickets;
use verbb\events\services\Tickets;
use verbb\events\services\TicketTypes;

use verbb\events\integrations\klaviyoconnect\KlaviyoConnect;

use Craft;

use verbb\base\BaseHelper;

trait PluginTrait
{
    // Static Properties
    // =========================================================================

    public static $plugin;


    // Public Methods
    // =========================================================================

    public function getEvents()
    {
        return $this->get('events');
    }

    public function getEventTypes()
    {
        return $this->get('eventTypes');
    }

    public function getKlaviyoConnect()
    {
        return $this->get('klaviyoConnect');
    }

    public function getPdf()
    {
        return $this->get('pdf');
    }

    public function getPurchasedTickets()
    {
        return $this->get('purchasedTickets');
    }

    public function getTickets()
    {
        return $this->get('tickets');
    }

    public function getTicketTypes()
    {
        return $this->get('ticketTypes');
    }

    public static function log($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'events');
    }

    public static function error($message)
    {
        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'events');
    }


    // Private Methods
    // =========================================================================

    private function _setPluginComponents()
    {
        $this->setComponents([
            'events' => EventsService::class,
            'eventTypes' => EventTypes::class,
            'klaviyoConnect' => KlaviyoConnect::class,
            'pdf' => Pdf::class,
            'purchasedTickets' => PurchasedTickets::class,
            'tickets' => Tickets::class,
            'ticketTypes' => TicketTypes::class,
        ]);

        BaseHelper::registerModule();
    }

    private function _setLogging()
    {
        Craft::getLogger()->dispatcher->targets[] = new FileTarget([
            'logFile' => Craft::getAlias('@storage/logs/events.log'),
            'categories' => ['events'],
        ]);
    }

}