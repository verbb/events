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

        $exportString = Events::$plugin->getIcs()->getCalendar([$event]);

        header('Content-type: text/calendar; charset=utf-8');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . strlen($exportString));
        header('Content-Disposition: attachment; filename=' . time() . '.ics');

        echo $exportString;

        exit();
    }

    public function actionEventType()
    {
        $request = Craft::$app->getRequest();

        $typeId = $request->getParam('typeId');
        $events = Event::find()->typeId($typeId)->endDate(null)->all();

        $exportString = Events::$plugin->getIcs()->getCalendar($events);

        header('Content-type: text/calendar; charset=utf-8');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . strlen($exportString));
        header('Content-Disposition: attachment; filename=' . time() . '.ics');

        echo $exportString;

        exit();
    }

}