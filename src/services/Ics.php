<?php
namespace verbb\events\services;

use verbb\events\elements\Event;
use verbb\events\elements\Session;

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

    public function getIcsEvent(Event|Session $element): ?CalendarEvent
    {
        if ($element instanceof Session) {
            $event = $element->getEvent();

            if (!$event) {
                return null;
            }
        } else {
            $event = $element;
        }

        if (!$element->startDate || !$element->endDate) {
            return null;
        }

        $eventType = $event->getType();

        $descriptionFieldHandle = $eventType->icsDescriptionFieldHandle;
        $locationFieldHandle = $eventType->icsLocationFieldHandle;

        // See if we need to override the timezone for events
        $icsTimezone = $eventType->icsTimezone ?? '';

        if ($icsTimezone == '') {
            $startDate = $element->startDate;
            $endDate = $element->endDate;
        } else {
            $timezone = new DateTimeZone($icsTimezone);

            $startDate = $element->startDate->setTimeZone($timezone);
            $endDate = $element->endDate->setTimeZone($timezone);
        }

        $icsEvent = (new CalendarEvent())
            ->setStart($startDate)
            ->setEnd($endDate)
            ->setCreated($element->dateCreated)
            ->setLastModified($element->dateUpdated)
            ->setSummary($event->title)
            ->setStatus($element->status)
            ->setUrl($event->url)
            ->setUid($element->uid);

        if ($descriptionFieldHandle && isset($event->{$descriptionFieldHandle})) {
            $icsEvent->setDescription($event->{$descriptionFieldHandle});
        }

        if ($locationFieldHandle && isset($event->{$locationFieldHandle})) {
            $location = new Location();
            $location->setName($event->{$locationFieldHandle});

            $icsEvent->addLocation($location);
        }

        return $icsEvent;
    }

}
