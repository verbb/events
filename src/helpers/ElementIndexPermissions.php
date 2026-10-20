<?php
namespace verbb\events\helpers;

use verbb\events\Events;
use verbb\events\controllers\ElementIndexesController;
use verbb\events\elements\Event as EventElement;
use verbb\events\elements\Session;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;
use verbb\events\elements\db\EventQuery;
use verbb\events\elements\db\SessionQuery;
use verbb\events\elements\db\TicketQuery;
use verbb\events\elements\db\TicketTypeQuery;

use Craft;
use craft\controllers\ElementIndexesController as CraftElementIndexesController;
use craft\db\Query;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;

use yii\base\Event;
use yii\base\InvalidConfigException;

class ElementIndexPermissions
{
    // Static Methods
    // =========================================================================

    public static function registerRequestHandlers(): void
    {
        $definition = Craft::$app->controllerMap['element-indexes'] ?? CraftElementIndexesController::class;

        Craft::$app->controllerMap['element-indexes'] = function($id, $module) use ($definition) {
            $elementType = Craft::$app->getRequest()->getParam('elementType');

            if (self::_permission($elementType) !== null) {
                $config = is_string($definition) ? ['class' => $definition] : $definition;

                if (is_array($config) && ($config['class'] ?? null) === CraftElementIndexesController::class) {
                    $config['class'] = ElementIndexesController::class;
                }

                $controller = Craft::createObject($config, [$id, $module]);

                if (!$controller instanceof ElementIndexesController) {
                    throw new InvalidConfigException('Custom element index controllers must extend the Events element index controller to support Events indexes.');
                }

                return $controller;
            }

            return Craft::createObject($definition, [$id, $module]);
        };

        Event::on(ElementQuery::class, ElementQuery::EVENT_AFTER_PREPARE, function(Event $event): void {
            $query = $event->sender;

            if ($query instanceof EventQuery || $query instanceof SessionQuery || $query instanceof TicketTypeQuery || $query instanceof TicketQuery) {
                $query->applyElementIndexScope();
            }
        });
    }

    public static function restrictQuery(ElementQueryInterface $query): void
    {
        if (!$query instanceof EventQuery && !$query instanceof SessionQuery && !$query instanceof TicketTypeQuery && !$query instanceof TicketQuery) {
            return;
        }

        $user = Craft::$app->getUser()->getIdentity();

        if ($user?->admin) {
            return;
        }

        $permission = self::_permission($query->elementType);
        $typeIds = [];
        $siteIds = [];

        foreach (Events::$plugin->getEventTypes()->getAllEventTypes() as $type) {
            if ($user && $user->can("$permission:$type->uid")) {
                $typeIds[] = $type->id;
            }
        }

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            if ($user && $user->can("editSite:$site->uid")) {
                $siteIds[] = $site->id;
            }
        }

        $query->restrictElementIndex(['typeIds' => $typeIds, 'siteIds' => $siteIds]);
    }

    public static function applyScope(ElementQuery $query, array $scope): void
    {
        if ($query instanceof TicketQuery) {
            $events = (new Query())
                ->select('id')
                ->from('{{%events_events}}')
                ->where(['typeId' => $scope['typeIds']]);
            $condition = ['events_tickets.eventId' => $events];
        } else {
            $condition = [
                'events_events.typeId' => $scope['typeIds'],
                'elements_sites.siteId' => $scope['siteIds'],
            ];
        }

        // Nested owner joins must remain restricted when the outer query reloads matching IDs.
        $query->subQuery->andWhere($condition);
        $query->query->andWhere($condition);
    }

    private static function _permission(mixed $elementType): ?string
    {
        if (!is_string($elementType)) {
            return null;
        }

        foreach ([Session::class => 'events-viewSessions', TicketType::class => 'events-viewTicketTypes', EventElement::class => 'events-viewEvents', Ticket::class => 'events-viewEvents'] as $class => $permission) {
            if (is_a($elementType, $class, true)) {
                return $permission;
            }
        }

        return null;
    }
}
