<?php
namespace verbb\events;

use verbb\events\base\PluginTrait;
use verbb\events\elements\Event as EventElement;
use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;
use verbb\events\helpers\ProjectConfigData;
use verbb\events\fields\Events as EventsField;
use verbb\events\models\Settings;
use verbb\events\services\EventTypes;
use verbb\events\variables\EventsVariable;

use Craft;
use craft\base\Plugin;
use craft\events\PluginEvent;
use craft\events\RebuildConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Plugins;
use craft\services\ProjectConfig;
use craft\services\Sites;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use craft\commerce\services\Purchasables;
use craft\commerce\elements\Order;
use craft\commerce\services\OrderAdjustments;

use yii\base\Event;

use fostercommerce\klaviyoconnect\services\Track;
use fostercommerce\klaviyoconnect\models\EventProperties;

class Events extends Plugin
{
    // Public Properties
    // =========================================================================

    public $schemaVersion = '1.0.11';
    public $hasCpSettings = true;
    public $hasCpSection = true;

    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init()
    {
        parent::init();

        self::$plugin = $this;

        $this->_setPluginComponents();
        $this->_registerCpRoutes();
        $this->_registerFieldTypes();
        $this->_registerPermissions();
        $this->_registerCraftEventListeners();
        $this->_registerThirdPartyEventListeners();
        $this->_registerProjectConfigEventListeners();
        $this->_registerVariables();
        $this->_registerElementTypes();
        $this->_registerPurchasableTypes();
    }

    public function getPluginName()
    {
        return Craft::t('events', $this->getSettings()->pluginName);
    }

    public function getSettingsResponse()
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('events/settings'));
    }

    public function getCpNavItem(): array
    {
        $nav = parent::getCpNavItem();

        $nav['label'] = $this->getPluginName();

        if (count($this->getEventTypes()->getEditableEventTypes()) > 0) {
            if (Craft::$app->getUser()->checkPermission('events-manageEvents')) {
                $nav['subnav']['events'] = [
                    'label' => Craft::t('events', 'Events'),
                    'url' => 'events/events',
                ];
            }
        }

        if (Craft::$app->getUser()->checkPermission('events-managePurchasedTickets')) {
            $nav['subnav']['purchasedTickets'] = [
                'label' => Craft::t('events', 'Purchased Tickets'),
                'url' => 'events/purchased-tickets',
            ];
        }

        if (Craft::$app->getUser()->checkPermission('events-manageEventTypes')) {
            $nav['subnav']['eventTypes'] = [
                'label' => Craft::t('events', 'Event Types'),
                'url' => 'events/event-types',
            ];
        }

        if (Craft::$app->getUser()->checkPermission('events-manageTicketTypes')) {
            $nav['subnav']['ticketTypes'] = [
                'label' => Craft::t('events', 'Ticket Types'),
                'url' => 'events/ticket-types',
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $nav['subnav']['settings'] = [
                'label' => Craft::t('events', 'Settings'),
                'url' => 'events/settings',
            ];
        }

        return $nav;
    }


    // Protected Methods
    // =========================================================================

    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }


    // Private Methods
    // =========================================================================

    private function _registerCpRoutes()
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'events/purchased-tickets' => 'events/purchased-tickets',
                'events/purchased-tickets/<purchasedTicketId:\d+>' => 'events/purchased-tickets/edit',

                'events/event-types/new' => 'events/event-types/edit',
                'events/event-types/<eventTypeId:\d+>' => 'events/event-types/edit',
                
                'events/events/<eventTypeHandle:{handle}>' => 'events/events/index',
                'events/events/<eventTypeHandle:{handle}>/new' => 'events/events/edit',
                'events/events/<eventTypeHandle:{handle}>/new/<siteHandle:{handle}>' => 'events/events/edit',
                'events/events/<eventTypeHandle:{handle}>/<eventId:\d+><slug:(?:-[^\/]*)?>' => 'events/events/edit',
                'events/events/<eventTypeHandle:{handle}>/<eventId:\d+><slug:(?:-[^\/]*)?>/<siteHandle:{handle}>' => 'events/events/edit',

                'events/ticket-types/new' => 'events/ticket-types/edit',
                'events/ticket-types/<ticketTypeId:\d+>' => 'events/ticket-types/edit',
                
                'events/tickets/new' => 'events/tickets/edit',
                'events/tickets/<ticketId:\d+>' => 'events/tickets/edit',

                'events/settings' => 'events/base/settings',
            ]);
        });
    }

    private function _registerElementTypes()
    {
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $e) {
            $e->types[] = EventElement::class;
            $e->types[] = Ticket::class;
            $e->types[] = TicketType::class;
            $e->types[] = PurchasedTicket::class;
        });
    }

    private function _registerFieldTypes()
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = EventsField::class;
        });
    }

    private function _registerPurchasableTypes()
    {
        Event::on(Purchasables::class, Purchasables::EVENT_REGISTER_PURCHASABLE_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Ticket::class;
        });
    }

    private function _registerPermissions()
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event) {
            $eventTypes = $this->getEventTypes()->getAllEventTypes();

            $eventTypePermissions = [];

            foreach ($eventTypes as $eventType) {
                $suffix = ':' . $eventType->uid;
                $eventTypePermissions['events-manageEventType' . $suffix] = ['label' => Craft::t('events', 'Manage “{type}” events', ['type' => $eventType->name])];
            }
            
            $event->permissions[Craft::t('events', 'Events')] = [
                'events-manageEventTypes' => ['label' => Craft::t('events', 'Manage event types')],
                'events-manageEvents' => ['label' => Craft::t('events', 'Manage events'), 'nested' => $eventTypePermissions],
                'events-manageTicketTypes' => ['label' => Craft::t('events', 'Manage ticket types')],
                'events-managePurchasedTickets' => ['label' => Craft::t('events', 'Manage purchased tickets')],
            ];
        });
    }

    private function _registerVariables()
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $variable = $event->sender;
            $variable->set('events', EventsVariable::class);
        });
    }

    private function _registerProjectConfigEventListeners()
    {
        $projectConfigService = Craft::$app->getProjectConfig();

        $eventTypeService = $this->getEventTypes();
        $projectConfigService->onAdd(EventTypes::CONFIG_EVENTTYPES_KEY . '.{uid}', [$eventTypeService, 'handleChangedEventType'])
            ->onUpdate(EventTypes::CONFIG_EVENTTYPES_KEY . '.{uid}', [$eventTypeService, 'handleChangedEventType'])
            ->onRemove(EventTypes::CONFIG_EVENTTYPES_KEY . '.{uid}', [$eventTypeService, 'handleDeletedEventType']);
        Event::on(Fields::class, Fields::EVENT_AFTER_DELETE_FIELD, [$eventTypeService, 'pruneDeletedField']);
        Event::on(Sites::class, Sites::EVENT_AFTER_DELETE_SITE, [$eventTypeService, 'pruneDeletedSite']);

        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function (RebuildConfigEvent $event) {
            $event->config['events'] = ProjectConfigData::rebuildProjectConfig();
        });
    }

    private function _registerCraftEventListeners()
    {
        Event::on(Sites::class, Sites::EVENT_AFTER_SAVE_SITE, [$this->getEventTypes(), 'afterSaveSiteHandler']);
        Event::on(Sites::class, Sites::EVENT_AFTER_SAVE_SITE, [$this->getEvents(), 'afterSaveSiteHandler']);

        // Ensure Commerce is installed
        Event::on(Plugins::class, Plugins::EVENT_BEFORE_INSTALL_PLUGIN, function (PluginEvent $event) {
            if ($event->plugin === $this && !Craft::$app->plugins->isPluginInstalled('commerce')) {
                throw new \Exception('Events required Commerce to be installed.');
            }
        });
    }

    private function _registerThirdPartyEventListeners()
    {
        if (class_exists(Track::class)) {
            Event::on(Track::class, Track::ADD_LINE_ITEM_CUSTOM_PROPERTIES, [$this->getKlaviyoConnect(), 'addLineItemCustomProperties']);
        }
    }

}
