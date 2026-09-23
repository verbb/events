<?php
namespace verbb\events\services;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\models\EventType;

use verbb\base\services\Templates as BaseTemplates;

class Templates extends BaseTemplates
{
    // Properties
    // =========================================================================

    public string $pluginClass = Events::class;
    public string|false|null $sandboxedAutoescape = false;


    // Public Methods
    // =========================================================================

    public function getSandboxedVariables(): array
    {
        return $this->getSiteTemplateVariables();
    }

    public function getDefaultSandboxedAllowedProperties(): array
    {
        return [
            Event::class => ['allDay', 'capacity', 'startDate', 'endDate', 'postDate', 'expiryDate', 'type', 'typeId'],
            EventType::class => ['id', 'uid', 'name', 'handle'],
        ] + parent::getDefaultSandboxedAllowedProperties();
    }
}
