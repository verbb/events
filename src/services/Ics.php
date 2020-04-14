<?php
namespace verbb\events\services;

use verbb\events\elements\Event;

use Craft;
use craft\db\Query;
use craft\events\SiteEvent;
use craft\helpers\App;
use craft\queue\jobs\ResaveElements;

use yii\base\Component;
use yii\base\Exception;

use Jsvrcek\ICS\Model\Calendar;
use Jsvrcek\ICS\Model\CalendarEvent;
use Jsvrcek\ICS\Utility\Formatter;
use Jsvrcek\ICS\CalendarStream;
use Jsvrcek\ICS\CalendarExport;

class Ics extends Component
{
    // Public Methods
    // =========================================================================

    public function getCalendar($events)
    {
        // Set the overall timezone to UTC. Individual events take care of timezone
        $timezone = new \DateTimeZone('UTC');

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
