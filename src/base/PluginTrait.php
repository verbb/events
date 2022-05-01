<?php
namespace verbb\events\base;

use verbb\events\Events;
use verbb\events\services\Events as EventsService;
use verbb\events\services\EventTypes;
use verbb\events\services\Ics;
use verbb\events\services\Pdf;
use verbb\events\services\PurchasedTickets;
use verbb\events\services\Tickets;
use verbb\events\services\TicketTypes;

use verbb\events\integrations\klaviyoconnect\KlaviyoConnect;
use verbb\base\BaseHelper;

use Craft;

use yii\log\Logger;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static Events $plugin;


    // Static Methods
    // =========================================================================

    public static function log(string $message, array $params = []): void
    {
        $message = Craft::t('events', $message, $params);

        Craft::getLogger()->log($message, Logger::LEVEL_INFO, 'events');
    }

    public static function error(string $message, array $params = []): void
    {
        $message = Craft::t('events', $message, $params);

        Craft::getLogger()->log($message, Logger::LEVEL_ERROR, 'events');
    }


    // Public Methods
    // =========================================================================

    public function getEvents(): EventsService
    {
        return $this->get('events');
    }

    public function getEventTypes(): EventTypes
    {
        return $this->get('eventTypes');
    }

    public function getIcs(): Ics
    {
        return $this->get('ics');
    }

    public function getKlaviyoConnect(): KlaviyoConnect
    {
        return $this->get('klaviyoConnect');
    }

    public function getPdf(): Pdf
    {
        return $this->get('pdf');
    }

    public function getPurchasedTickets(): PurchasedTickets
    {
        return $this->get('purchasedTickets');
    }

    public function getTickets(): Tickets
    {
        return $this->get('tickets');
    }

    public function getTicketTypes(): TicketTypes
    {
        return $this->get('ticketTypes');
    }


    // Private Methods
    // =========================================================================

    private function _registerComponents(): void
    {
        $this->setComponents([
            'events' => EventsService::class,
            'eventTypes' => EventTypes::class,
            'ics' => Ics::class,
            'klaviyoConnect' => KlaviyoConnect::class,
            'pdf' => Pdf::class,
            'purchasedTickets' => PurchasedTickets::class,
            'tickets' => Tickets::class,
            'ticketTypes' => TicketTypes::class,
        ]);

        BaseHelper::registerModule();
    }

    private function _registerLogTarget(): void
    {
        BaseHelper::setFileLogging('events');
    }

}