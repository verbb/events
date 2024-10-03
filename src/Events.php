<?php
namespace verbb\events;

use verbb\events\base\PluginTrait;
use verbb\events\elements\Event as EventElement;
use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\Session;
use verbb\events\elements\Ticket;
use verbb\events\elements\TicketType;
use verbb\events\gql\interfaces\EventInterface;
use verbb\events\gql\interfaces\PurchasedTicketInterface;
use verbb\events\gql\interfaces\SessionInterface;
use verbb\events\gql\interfaces\TicketTypeInterface;
use verbb\events\gql\interfaces\TicketInterface;
use verbb\events\gql\queries\EventQuery;
use verbb\events\gql\queries\PurchasedTicketQuery;
use verbb\events\gql\queries\SessionQuery;
use verbb\events\gql\queries\TicketTypeQuery;
use verbb\events\gql\queries\TicketQuery;
use verbb\events\helpers\ProjectConfigData;
use verbb\events\fields\Events as EventsField;
use verbb\events\fieldlayoutelements as LayoutFields;
use verbb\events\integrations\feedme\Event as FeedMeEvent;
use verbb\events\integrations\seomatic\Event as SeomaticEvent;
use verbb\events\models\Settings;
use verbb\events\services\EventTypes;
use verbb\events\variables\EventsVariable;

use Craft;
use craft\base\Plugin;
use craft\console\Application as ConsoleApplication;
use craft\console\Controller as ConsoleController;
use craft\console\controllers\ResaveController;
use craft\events\DefineConsoleActionsEvent;
use craft\events\DefineFieldLayoutFieldsEvent;
use craft\events\PluginEvent;
use craft\events\RebuildConfigEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\events\RegisterGqlSchemaComponentsEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Gc;
use craft\services\Gql;
use craft\services\Plugins;
use craft\services\ProjectConfig;
use craft\services\Sites;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;

use craft\commerce\services\Emails;
use craft\commerce\services\Purchasables;

use yii\base\Event;

use fostercommerce\klaviyoconnect\services\Track;
use fostercommerce\klaviyoconnect\models\EventProperties;

use craft\feedme\events\RegisterFeedMeElementsEvent;
use craft\feedme\services\Elements as FeedMeElements;

use nystudio107\seomatic\services\SeoElements;

use Exception;

class Events extends Plugin
{
    // Properties
    // =========================================================================

    public bool $hasCpSection = true;
    public bool $hasCpSettings = true;
    public string $schemaVersion = '1.1.1';
    public string $minVersionRequired = '1.4.20';


    // Traits
    // =========================================================================

    use PluginTrait;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->_registerFieldTypes();
        $this->_registerEventHandlers();
        $this->_registerProjectConfigEventHandlers();
        $this->_registerVariables();
        $this->_registerElementTypes();
        $this->_registerPurchasableTypes();
        $this->_registerFieldLayoutElements();
        $this->_registerGraphQl();
        $this->_registerGarbageCollection();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpRoutes();
        }

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->_registerResaveCommand();
        }
        
        if (Craft::$app->getEdition() === Craft::Pro) {
            $this->_registerPermissions();
        }
    }

    public function getPluginName(): string
    {
        return Craft::t('events', $this->getSettings()->pluginName);
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('events/settings'));
    }

    public function getCpNavItem(): ?array
    {
        $nav = parent::getCpNavItem();

        $nav['label'] = $this->getPluginName();

        $nav['subnav']['events'] = [
            'label' => Craft::t('events', 'Events'),
            'url' => 'events/events',
        ];

        if (Craft::$app->getUser()->checkPermission('events-viewPurchasedTickets')) {
            $nav['subnav']['purchasedTickets'] = [
                'label' => Craft::t('events', 'Purchased Tickets'),
                'url' => 'events/purchased-tickets',
            ];
        }

        if (Craft::$app->getUser()->getIsAdmin() && Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $nav['subnav']['eventTypes'] = [
                'label' => Craft::t('events', 'Event Types'),
                'url' => 'events/event-types',
            ];

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

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['events/events'] = 'events/events/index';
            $event->rules['events/events/<eventTypeHandle:{handle}>'] = 'events/events/index';
            $event->rules['events/events/<eventType:{handle}>/new'] = 'events/events/create';
            $event->rules['events/events/<eventTypeHandle:{handle}>/<elementId:\d+><slug:(?:-[^\/]*)?>'] = 'elements/edit';

            $event->rules['events/sessions/<elementId:\d+>'] = 'elements/edit';
            $event->rules['events/ticket-types/<elementId:\d+>'] = 'elements/edit';

            $event->rules['events/purchased-tickets'] = 'events/purchased-tickets';
            $event->rules['events/purchased-tickets/<purchasedTicketId:\d+>'] = 'events/purchased-tickets/edit';

            $event->rules['events/event-types/new'] = 'events/event-types/edit';
            $event->rules['events/event-types/<eventTypeId:\d+>'] = 'events/event-types/edit';

            $event->rules['events/settings'] = 'events/base/settings';
        });
    }

    private function _registerElementTypes(): void
    {
        Event::on(Elements::class, Elements::EVENT_REGISTER_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event): void {
            $event->types[] = EventElement::class;
            $event->types[] = PurchasedTicket::class;
            $event->types[] = Session::class;
            $event->types[] = Ticket::class;
            $event->types[] = TicketType::class;
        });
    }

    private function _registerFieldTypes(): void
    {
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function(RegisterComponentTypesEvent $event): void {
            $event->types[] = EventsField::class;
        });
    }

    private function _registerPurchasableTypes(): void
    {
        Event::on(Purchasables::class, Purchasables::EVENT_REGISTER_PURCHASABLE_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event): void {
            $event->types[] = Ticket::class;
        });
    }

    private function _registerPermissions(): void
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function(RegisterUserPermissionsEvent $event): void {
            $eventTypes = $this->getEventTypes()->getAllEventTypes();

            $eventTypePermissions = [];

            foreach ($eventTypes as $eventType) {
                $suffix = ':' . $eventType->uid;

                $eventTypePermissions['events-viewEvents' . $suffix] = [
                    'label' => Craft::t('events', 'View “{type}” events', ['type' => $eventType->name]),
                    'nested' => [
                        "events-createEvents$suffix" => [
                            'label' => Craft::t('events', 'Create events'),
                        ],
                        "events-editEvents$suffix" => [
                            'label' => Craft::t('events', 'Edit events'),
                        ],
                        "events-deleteEvents$suffix" => [
                            'label' => Craft::t('events', 'Delete events'),
                        ],
                        "events-viewSessions$suffix" => [
                            'label' => Craft::t('events', 'View sessions'),
                            'nested' => [
                                "events-createSessions$suffix" => [
                                    'label' => Craft::t('events', 'Create sessions'),
                                ],
                                "events-editSessions$suffix" => [
                                    'label' => Craft::t('events', 'Edit sessions'),
                                ],
                                "events-deleteSessions$suffix" => [
                                    'label' => Craft::t('events', 'Delete sessions'),
                                ],
                            ],
                        ],
                        "events-viewTicketTypes$suffix" => [
                            'label' => Craft::t('events', 'View ticket types'),
                            'nested' => [
                                "events-createTicketTypes$suffix" => [
                                    'label' => Craft::t('events', 'Create ticket types'),
                                ],
                                "events-editTicketTypes$suffix" => [
                                    'label' => Craft::t('events', 'Edit ticket types'),
                                ],
                                "events-deleteTicketTypes$suffix" => [
                                    'label' => Craft::t('events', 'Delete ticket types'),
                                ],
                            ],
                        ],
                    ],
                ];
            }

            $event->permissions[] = [
                'heading' => Craft::t('events', 'Events'),
                'permissions' => $eventTypePermissions + [
                    'events-viewPurchasedTickets' => [
                        'label' => Craft::t('events', 'View purchased tickets'),
                        'nested' => [
                            'events-editPurchasedTickets' => [
                                'label' => Craft::t('events', 'Edit purchased tickets'),
                            ],
                            'events-deletePurchasedTickets' => [
                                'label' => Craft::t('events', 'Delete purchased tickets'),
                            ],
                        ],
                    ],
                    'events-checkInTickets' => ['label' => Craft::t('events', 'Check in purchased tickets')],
                ],
            ];
        });
    }

    private function _registerVariables(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('events', EventsVariable::class);
        });
    }

    private function _registerProjectConfigEventHandlers(): void
    {
        $projectConfigService = Craft::$app->getProjectConfig();

        $eventTypeService = $this->getEventTypes();
        $projectConfigService->onAdd(EventTypes::CONFIG_EVENTTYPES_KEY . '.{uid}', [$eventTypeService, 'handleChangedEventType'])
            ->onUpdate(EventTypes::CONFIG_EVENTTYPES_KEY . '.{uid}', [$eventTypeService, 'handleChangedEventType'])
            ->onRemove(EventTypes::CONFIG_EVENTTYPES_KEY . '.{uid}', [$eventTypeService, 'handleDeletedEventType']);
        Event::on(Sites::class, Sites::EVENT_AFTER_DELETE_SITE, [$eventTypeService, 'pruneDeletedSite']);

        Event::on(ProjectConfig::class, ProjectConfig::EVENT_REBUILD, function(RebuildConfigEvent $event) {
            $event->config['events'] = ProjectConfigData::rebuildProjectConfig();
        });
    }

    private function _registerEventHandlers(): void
    {
        Event::on(Sites::class, Sites::EVENT_AFTER_SAVE_SITE, [$this->getEventTypes(), 'afterSaveSiteHandler']);
        Event::on(Sites::class, Sites::EVENT_AFTER_SAVE_SITE, [$this->getEvents(), 'afterSaveSiteHandler']);

        // Potentially add the PDF to an email
        Event::on(Emails::class, Emails::EVENT_BEFORE_SEND_MAIL, [$this->getTickets(), 'onBeforeSendEmail']);
        Event::on(Emails::class, Emails::EVENT_AFTER_SEND_MAIL, [$this->getTickets(), 'onAfterSendEmail']);

        // Ensure Commerce is installed
        Event::on(Plugins::class, Plugins::EVENT_BEFORE_INSTALL_PLUGIN, function(PluginEvent $event) {
            if ($event->plugin === $this && !Craft::$app->plugins->isPluginInstalled('commerce')) {
                throw new Exception('Events required Commerce to be installed.');
            }
        });

        if (class_exists(Track::class)) {
            Event::on(Track::class, Track::ADD_LINE_ITEM_CUSTOM_PROPERTIES, [$this->getKlaviyoConnect(), 'addLineItemCustomProperties']);
        }

        // Support Feed Me
        if (class_exists(FeedMeElements::class)) {
            Event::on(FeedMeElements::class, FeedMeElements::EVENT_REGISTER_FEED_ME_ELEMENTS, function(RegisterFeedMeElementsEvent $event) {
                $event->elements[] = FeedMeEvent::class;
            });
        }

        // Support SEOmatic
        if (class_exists(SeoElements::class)) {
            Event::on(SeoElements::class, SeoElements::EVENT_REGISTER_SEO_ELEMENT_TYPES, function(RegisterComponentTypesEvent $event) {
                $event->types[] = SeomaticEvent::class;
            });
        }
    }

    private function _registerResaveCommand(): void
    {
        if (!Craft::$app instanceof ConsoleApplication) {
            return;
        }

        Event::on(ResaveController::class, ConsoleController::EVENT_DEFINE_ACTIONS, function(DefineConsoleActionsEvent $event) {
            $event->actions['events-events'] = [
                'action' => function(): int {
                    $controller = Craft::$app->controller;
                    
                    return $controller->resaveElements(EventElement::class);
                },
                'options' => [],
                'helpSummary' => 'Re-saves Events events.',
            ];

            $event->actions['events-sessions'] = [
                'action' => function(): int {
                    $controller = Craft::$app->controller;

                    return $controller->resaveElements(Session::class);
                },
                'options' => [],
                'helpSummary' => 'Re-saves Events sessions.',
            ];

            $event->actions['events-ticket-types'] = [
                'action' => function(): int {
                    $controller = Craft::$app->controller;

                    return $controller->resaveElements(TicketType::class);
                },
                'options' => [],
                'helpSummary' => 'Re-saves Events ticket types.',
            ];

            $event->actions['events-tickets'] = [
                'action' => function(): int {
                    $controller = Craft::$app->controller;

                    return $controller->resaveElements(Ticket::class);
                },
                'options' => [],
                'helpSummary' => 'Re-saves Events tickets.',
            ];

            $event->actions['events-purchased-tickets'] = [
                'action' => function(): int {
                    $controller = Craft::$app->controller;

                    return $controller->resaveElements(PurchasedTicket::class);
                },
                'options' => [],
                'helpSummary' => 'Re-saves Events purchased tickets.',
            ];
        });
    }

    private function _registerGarbageCollection(): void
    {
        Event::on(Gc::class, Gc::EVENT_RUN, function(Event $event) {
            
        });
    }

    private function _registerGraphQl(): void
    {
        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_TYPES, function(RegisterGqlTypesEvent $event) {
            $event->types[] = EventInterface::class;
            $event->types[] = SessionInterface::class;
            $event->types[] = TicketTypeInterface::class;
            $event->types[] = TicketInterface::class;
            $event->types[] = PurchasedTicketInterface::class;
        });

        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_QUERIES, function(RegisterGqlQueriesEvent $event) {
            $event->queries = array_merge(
                $event->queries,
                EventQuery::getQueries(),
                SessionQuery::getQueries(),
                TicketTypeQuery::getQueries(),
                TicketQuery::getQueries(),
                PurchasedTicketQuery::getQueries()
            );
        });

        Event::on(Gql::class, Gql::EVENT_REGISTER_GQL_SCHEMA_COMPONENTS, function(RegisterGqlSchemaComponentsEvent $event) {
            $eventTypes = Events::$plugin->getEventTypes()->getAllEventTypes();

            if (!empty($eventTypes)) {
                $label = Craft::t('events', 'Events');

                foreach ($eventTypes as $eventType) {
                    $suffix = 'eventsEventTypes.' . $eventType->uid;
                    $event->queries[$label][$suffix . ':read'] = ['label' => Craft::t('events', 'View “{eventType}” events', ['eventType' => Craft::t('site', $eventType->name)])];
                }
            }
        });
    }

    private function _registerFieldLayoutElements(): void
    {
        Event::on(FieldLayout::class, FieldLayout::EVENT_DEFINE_NATIVE_FIELDS, static function(DefineFieldLayoutFieldsEvent $event) {
            if ($event->sender->type === EventElement::class) {
                $event->fields[] = LayoutFields\EventTitleField::class;
                $event->fields[] = LayoutFields\SessionsField::class;
                $event->fields[] = LayoutFields\TicketTypesField::class;
                $event->fields[] = LayoutFields\TicketsField::class;
                $event->fields[] = LayoutFields\PurchasedTicketsField::class;
            }

            if ($event->sender->type === Session::class) {
                $event->fields[] = LayoutFields\SessionStartDateTimeField::class;
                $event->fields[] = LayoutFields\SessionEndDateTimeField::class;
                $event->fields[] = LayoutFields\SessionAllDayField::class;
                $event->fields[] = LayoutFields\SessionFrequencyField::class;
                $event->fields[] = LayoutFields\PurchasedTicketsField::class;
            }

            if ($event->sender->type === TicketType::class) {
                $event->fields[] = LayoutFields\TicketTypeTitleField::class;
                $event->fields[] = LayoutFields\TicketTypeCapacityField::class;
                $event->fields[] = LayoutFields\TicketTypePriceField::class;
                $event->fields[] = LayoutFields\TicketTypeAllowedQtyField::class;
                $event->fields[] = LayoutFields\PurchasedTicketsField::class;
            }
        });
    }

}
