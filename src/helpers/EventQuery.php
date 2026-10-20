<?php
namespace verbb\events\helpers;

use verbb\events\elements\Session;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;

use craft\controllers\ElementIndexesController;
use craft\controllers\ElementSearchController;
use craft\helpers\ElementHelper;

use yii\base\ActionEvent;
use yii\base\Event;

class EventQuery
{
    // Static Methods
    // =========================================================================

    public static function registerRequestHandlers(): void
    {
        foreach ([ElementIndexesController::class, ElementSearchController::class] as $controllerClass) {
            Event::on($controllerClass, $controllerClass::EVENT_BEFORE_ACTION, [self::class, 'cleanseRequestCriteria']);
        }
    }

    public static function cleanseRequestCriteria(ActionEvent $event): void
    {
        $request = $event->sender->request;
        $isSearch = $event->sender instanceof ElementSearchController;
        $elementType = $isSearch ? $request->getBodyParam('elementType') : $request->getParam('elementType');

        if (!is_string($elementType) || (!is_a($elementType, Ticket::class, true) && !is_a($elementType, TicketType::class, true) && !is_a($elementType, Session::class, true))) {
            return;
        }

        $params = $request->getBodyParams();

        // Clean only submitted criteria, leaving trusted PHP/Twig query configuration intact.
        foreach ($isSearch ? ['criteria'] : ['baseCriteria', 'criteria'] as $key) {
            if (is_array($params[$key] ?? null) && is_array($params[$key]['hasEvent'] ?? null)) {
                $params[$key]['hasEvent'] = ElementHelper::cleanseQueryCriteria($params[$key]['hasEvent']);
            }
        }

        $request->setBodyParams($params);
    }
}
