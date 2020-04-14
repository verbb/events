<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\Event;

use Craft;
use craft\web\Controller;

use Jsvrcek\ICS\CalendarStream;
use Jsvrcek\ICS\CalendarExport;
use Jsvrcek\ICS\Model\Calendar;
use Jsvrcek\ICS\Model\CalendarEvent;
use Jsvrcek\ICS\Utility\Formatter;

class IcsController extends Controller
{
    // Properties
    // =========================================================================

    protected $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionIndex()
    {
        $request = Craft::$app->getRequest();

        $eventId = $request->getParam('eventId');
        $event = Event::find()->id($eventId)->endDate(null)->one();
        $icsEvent = $event->getIcsEvent();

        // Set the overall timezone to UTC. Individual events take care of timezone
        $timezone = new \DateTimeZone('UTC');

        $calendar = new Calendar();
        $calendar->setProdId('-//Verbb//Events//EN')
            ->setTimezone($timezone)
            ->addEvent($icsEvent);

        $calendarExport = new CalendarExport(new CalendarStream, new Formatter());
        $calendarExport->addCalendar($calendar);

        $exportString = $calendarExport->getStream();

        header('Content-type: text/calendar; charset=utf-8');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . strlen($exportString));

        echo $exportString;

        exit();
    }

}