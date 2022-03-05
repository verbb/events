<?php
namespace verbb\events\services;

use yii\base\Component;

use Jsvrcek\ICS\Model\Calendar;
use Jsvrcek\ICS\Utility\Formatter;
use Jsvrcek\ICS\CalendarStream;
use Jsvrcek\ICS\CalendarExport;

use DateTimeZone;

class Ics extends Component
{
    // Public Methods
    // =========================================================================

    public function getCalendar($events): string
    {
        // Set the overall timezone to UTC. Individual events take care of timezone
        $timezone = new DateTimeZone('UTC');

        $calendar = new Calendar();
        $calendar->setProdId('-//Verbb//Events//EN')
            ->setTimezone($timezone);

        foreach ($events as $event) {
            $icsEvent = $event->getIcsEvent();

            if ($icsEvent) {
                $calendar->addEvent($icsEvent);
            }
        }

        $calendarExport = new CalendarExport(new CalendarStream, new Formatter());
        $calendarExport->addCalendar($calendar);

        return $calendarExport->getStream();
    }

}
