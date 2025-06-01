<?php
namespace verbb\events\services;

use verbb\events\elements\Event;

use yii\base\Component;

use DateTimeZone;

use Jsvrcek\ICS\Model\Calendar;
use Jsvrcek\ICS\Model\CalendarEvent;
use Jsvrcek\ICS\Model\Description\Location;
use Jsvrcek\ICS\Utility\Formatter;
use Jsvrcek\ICS\CalendarStream;
use Jsvrcek\ICS\CalendarExport;

class Ics extends Component
{
    // Public Methods
    // =========================================================================

    public function getCalendar(array $events): string
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

    public function getIcsEvent(Event $event): ?CalendarEvent
    {
        if (!$event->startDate || !$event->endDate) {
            return null;
        }

        $eventType = $event->getType();

        $description = $event->title;
        $location = '';

        $descriptionFieldHandle = $eventType->icsDescriptionFieldHandle;
        $locationFieldHandle = $eventType->icsLocationFieldHandle;

        // See if we need to override the timezone for events
        $icsTimezone = $eventType->icsTimezone ?? '';

        if ($icsTimezone == '') {
            $startDate = $event->startDate;
            $endDate = $event->endDate;
        } else {
            $timezone = new DateTimeZone($icsTimezone);

            $startDate = $event->startDate->setTimeZone($timezone);
            $endDate = $event->endDate->setTimeZone($timezone);
        }

        $event = (new CalendarEvent())
            ->setStart($startDate)
            ->setEnd($endDate)
            ->setCreated($event->dateCreated)
            ->setLastModified($event->dateUpdated)
            ->setSummary($event->title)
            ->setStatus($event->status)
            ->setUrl($event->url)
            ->setUid($event->uid);

        if ($descriptionFieldHandle && isset($event->{$descriptionFieldHandle})) {
            $event->setDescription($event->{$descriptionFieldHandle});
        }

        if ($locationFieldHandle && isset($event->{$locationFieldHandle})) {
            $location = new Location();
            $location->setName($event->{$locationFieldHandle});

            $event->addLocation($location);
        }

        return $event;
    }

}
