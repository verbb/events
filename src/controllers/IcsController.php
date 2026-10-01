<?php
namespace verbb\events\controllers;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\elements\Session;

use craft\web\Controller;

use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

class IcsController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = true;


    // Public Methods
    // =========================================================================

    public function actionIndex(): void
    {
        $sessionId = $this->_positiveIntegerParam('sessionId');

        if ($sessionId !== null) {
            $element = Session::find()
                ->id($sessionId)
                ->endDate(null)
                ->hasEvent(Event::find())
                ->one();
        } else {
            $eventId = $this->_positiveIntegerParam('eventId');

            if ($eventId === null) {
                throw new BadRequestHttpException('Missing required parameter: sessionId or eventId.');
            }

            $element = Event::find()->id($eventId)->endDate(null)->one();
        }

        if (!$element) {
            throw new NotFoundHttpException('No calendar event found.');
        }

        $exportString = Events::$plugin->getIcs()->getCalendar([$element]);

        header('Content-type: text/calendar; charset=utf-8');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . strlen($exportString));
        header('Content-Disposition: attachment; filename=' . time() . '.ics');

        echo $exportString;

        exit();
    }

    public function actionEventType(): void
    {
        $typeId = $this->request->getParam('typeId');
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


    // Private Methods
    // =========================================================================

    private function _positiveIntegerParam(string $name): ?int
    {
        $value = $this->request->getParam($name);

        if ($value === null || $value === '') {
            return null;
        }

        if (!is_int($value) && !is_string($value)) {
            throw new BadRequestHttpException("Invalid $name.");
        }

        $value = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
            ],
        ]);

        if ($value === false) {
            throw new BadRequestHttpException("Invalid $name.");
        }

        return $value;
    }

}
