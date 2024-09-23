<?php
namespace verbb\events\base;

use verbb\events\Events;
use verbb\events\services\Events as EventsService;
use verbb\events\services\EventTypes;
use verbb\events\services\Ics;
use verbb\events\services\Pdf;
use verbb\events\services\PurchasedTickets;
use verbb\events\services\Sessions;
use verbb\events\services\Tickets;

use verbb\base\LogTrait;
use verbb\base\helpers\Plugin;

use verbb\events\integrations\klaviyoconnect\KlaviyoConnect;

trait PluginTrait
{
    // Properties
    // =========================================================================

    public static ?Events $plugin = null;


    // Traits
    // =========================================================================

    use LogTrait;
    

    // Static Methods
    // =========================================================================

    public static function config(): array
    {
        Plugin::bootstrapPlugin('events');

        return [
            'components' => [
                'events' => EventsService::class,
                'eventTypes' => EventTypes::class,
                'ics' => Ics::class,
                'klaviyoConnect' => KlaviyoConnect::class,
                'pdf' => Pdf::class,
                'purchasedTickets' => PurchasedTickets::class,
                'sessions' => Sessions::class,
                'tickets' => Tickets::class,
            ],
        ];
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

    public function getSessions(): Sessions
    {
        return $this->get('sessions');
    }

    public function getTickets(): Tickets
    {
        return $this->get('tickets');
    }

}