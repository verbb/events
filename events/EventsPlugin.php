<?php

namespace Craft;

require __DIR__ . '/vendor/autoload.php';

class EventsPlugin extends BasePlugin
{
    // =========================================================================
    // PLUGIN INFO
    // =========================================================================

    public function getName()
    {
        return 'Events';
    }

    public function getVersion()
    {
        return '0.1.1';
    }

    public function getSchemaVersion()
    {
        return '0.1.0';
    }

    public function getDeveloper()
    {
        return 'Verbb';
    }

    public function getDeveloperUrl()
    {
        return 'http://verbb.io';
    }

    public function getPluginUrl()
    {
        return 'https://github.com/verbb/events';
    }

    public function getDocumentationUrl()
    {
        return 'https://verbb.io/craft-plugins/events/docs';
    }

    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/verbb/events/craft-2/changelog.json';
    }

    /**
     * Check for requirements only after the plugin is installed (because onBeforeInstall the plugin resources are not available).
     * Redirect to welcome screen if all dependencies are installed.
     *
     * @throws \CHttpException
     */
    public function onAfterInstall()
    {
        $dependencies = EventsHelper::getPluginService()->checkRequirements();

        if ($dependencies) {
            craft()->runController('events/plugin/checkRequirements');
        } else {
            craft()->request->redirect(UrlHelper::getCpUrl('events/welcome'));
        }
    }

    public function getRequiredPlugins()
    {
        return [
            [
                'name'    => 'Commerce',
                'handle'  => 'commerce',
                'url'     => 'https://craftcommerce.com',
                'version' => '1.2.0',
            ],
        ];
    }

    public function hasCpSection()
    {
        return true;
    }

    public function getSettingsUrl()
    {
        if (!EventsHelper::getLicenseService()->isLicensed()) {
            return 'events/settings/license';
        }

        return 'events/settings/general';
    }

    public function registerCpRoutes()
    {
        return [
            'events/eventtypes/new'                                                          => ['action' => 'events/eventTypes/edit'],
            'events/eventtypes/(?P<eventTypeId>\d+)'                                         => ['action' => 'events/eventTypes/edit'],
            'events/events/(?P<eventTypeHandle>{handle})'                                    => ['action' => 'events/events/index'],
            'events/events/(?P<eventTypeHandle>{handle})/new'                                => ['action' => 'events/events/edit'],
            'events/events/(?P<eventTypeHandle>{handle})/new/(?P<localeId>\w+)'              => ['action' => 'events/events/edit'],
            'events/events/(?P<eventTypeHandle>{handle})/(?P<eventId>\d+)'                   => ['action' => 'events/events/edit'],
            'events/events/(?P<eventTypeHandle>{handle})/(?P<eventId>\d+)/(?P<localeId>\w+)' => ['action' => 'events/events/edit'],
            'events/tickettypes/new'                                                         => ['action' => 'events/ticketTypes/edit'],
            'events/tickettypes/(?P<ticketTypeId>\d+)'                                       => ['action' => 'events/ticketTypes/edit'],
            'events/tickets/new'                                                             => ['action' => 'events/tickets/edit'],
            'events/tickets/(?P<ticketId>\d+)'                                               => ['action' => 'events/tickets/edit'],
            'events/settings/license'                                                        => ['action' => 'events/license/edit'],
            'events/settings/general'                                                        => ['action' => 'events/plugin/settings'],
        ];
    }

    protected function defineSettings()
    {
        return [
            'ticketSKULength'          => [AttributeType::Number, 'default' => 10],
            'ticketsPdfPath'           => [
                AttributeType::String,
                'required' => true,
                'default'  => 'shop/_pdf/tickets',
            ],
            'ticketsPdfFilenameFormat' => [
                AttributeType::String,
                'required' => true,
                'default'  => 'Tickets-{number}',
            ],
            'edition' => [AttributeType::Mixed, 'default' => -1],
        ];
    }


    // =========================================================================
    // HOOKS
    // =========================================================================

    public function init()
    {
        if (craft()->request->isCpRequest()) {
            EventsHelper::getLicenseService()->ping();
            craft()->templates->hook('events.prepCpTemplate', [$this, 'prepCpTemplate']);
            $this->_includeCpResources();
        }

        $this->_registerEventHandlers();
    }

    public function prepCpTemplate(&$context)
    {
        $context['subnav'] = [];

        if (craft()->userSession->checkPermission('events-manageEvents')) {
            $context['subnav']['events'] = [
                'label' => Craft::t('Events'),
                'url'   => 'events/events',
            ];
        }

        if (craft()->userSession->checkPermission('events-manageEventTypes')) {
            $context['subnav']['eventTypes'] = [
                'label' => Craft::t('Event Types'),
                'url'   => 'events/eventtypes',
            ];
        }

        if (craft()->userSession->checkPermission('events-manageTicketTypes')) {
            $context['subnav']['ticketTypes'] = [
                'label' => Craft::t('Ticket Types'),
                'url'   => 'events/tickettypes',
            ];
        }

        $settingsUrl = EventsHelper::getLicenseService()->isLicensed() ? 'general' : 'license';
        $context['subnav']['settings'] = [
            'label' => Craft::t('Settings'),
            'url'   => 'events/settings/' . $settingsUrl,
        ];
    }

    public function registerUserPermissions()
    {
        $eventTypes = EventsHelper::getEventTypesService()->getAllEventTypes('id');

        $eventTypePermissions = [];
        foreach ($eventTypes as $id => $eventType) {
            $suffix = ':' . $id;
            $eventTypePermissions["events-manageEventType" . $suffix] = [
                'label' => Craft::t('“{type}” events', ['type' => $eventType->name]),
            ];
        }

        $ticketTypes = EventsHelper::getTicketTypesService()->getAllTicketTypes('id');

        $ticketTypePermissions = [];
        foreach ($ticketTypes as $id => $ticketType) {
            $suffix = ':' . $id;
            $ticketTypePermissions["events-manageTicketType" . $suffix] = [
                'label' => Craft::t('“{type}” tickets', ['type' => $ticketType->title]),
            ];
        }

        return [
            'events-manageEventTypes' => ['label' => Craft::t('Manage event types')],
            'events-manageEvents'     => [
                'label'  => Craft::t('Manage events'),
                'nested' => $eventTypePermissions,
            ],
            'events-manageTicketType' => ['label' => Craft::t('Manage ticket types')],
            'events-manageTickets'    => [
                'label'  => Craft::t('Manage tickets'),
                'nested' => $ticketTypePermissions,
            ],
        ];
    }

    /**
     * Register CP alert
     *
     * @param $path
     * @param $fetch
     *
     * @return array|null
     */
    public function getCpAlerts($path, $fetch)
    {
        if ($path !== 'events/settings/license' && !EventsHelper::getLicenseService()->isLicensed()) {
            $alert = 'You haven’t entered your Events license key yet.';
            $alert .= '<a class="go" href="' . UrlHelper::getCpUrl('events/settings/license') . '">Resolve</a>';

            return [$alert];
        }

        return null;
    }


    // Private Methods
    // =========================================================================

    private function _includeCpResources()
    {
        if (craft()->request->getSegment(1) == 'events') {
            craft()->templates->includeCssResource('events/css/EventsPlugin.css');
            craft()->templates->includeCssResource('events/css/EventsEventEdit.css');

            craft()->templates->includeJsResource('events/js/Events.js');
            craft()->templates->includeJsResource('events/js/EventsEventEdit.js');
            craft()->templates->includeJsResource('events/js/EventsTicketEdit.js');
            craft()->templates->includeJsResource('events/js/EventsTicketIndex.js');
            craft()->templates->includeJsResource('events/js/EventsEventIndex.js');
        }
    }

    private function _registerEventHandlers()
    {
        craft()->on('commerce_lineItems.onPopulateLineItem', [
            '\Craft\Events_PurchasedTicketsService',
            'onPopulateLineItemHandler',
        ]);

        craft()->on('commerce_orders.onOrderComplete', [
            '\Craft\Events_PurchasedTicketsService',
            'onOrderCompleteHandler',
        ]);
    }
}
