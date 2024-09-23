<?php
namespace verbb\events\helpers;

use verbb\events\Events;

class ProjectConfigData
{
    // Static Methods
    // =========================================================================

    public static function rebuildProjectConfig(): array
    {
        $configData = [];

        $configData['eventTypes'] = self::_getEventTypeData();

        return array_filter($configData);
    }

    
    // Private Methods
    // =========================================================================

    private static function _getEventTypeData(): array
    {
        $data = [];

        foreach (Events::$plugin->getEventTypes()->getAllEventTypes() as $eventType) {
            $data[$eventType->uid] = $eventType->getConfig();
        }

        return $data;
    }
}
