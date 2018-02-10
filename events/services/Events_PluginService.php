<?php
namespace Craft;

class Events_PluginService extends BaseApplicationComponent
{
    // Public Methods
    // =========================================================================

    public function checkRequirements()
    {
        $dependencies = $this->getPluginDependencies();

        if (count($dependencies) > 0) {
            return $dependencies;
        }

        return null;
    }

    public function getPluginDependencies($missingOnly = true)
    {
        $dependencies = array();
        $plugins = EventsHelper::getPlugin()->getRequiredPlugins();

        foreach ($plugins as $key => $plugin) {
            $dependency = $this->_getPluginDependency($plugin);

            if ($missingOnly) {
                if ($dependency['isMissing']) {
                    $dependencies[] = $dependency;
                }
            } else {
                $dependencies[] = $dependency;
            }
        }

        return $dependencies;
    }


    // Private Methods
    // =========================================================================

    private function _getPluginDependency(array $dependency)
    {
        $isDependencyMissing = true;
        $requiresUpdate = true;

        $plugin = craft()->plugins->getPlugin($dependency['handle'], false);

        if ($plugin) {
            if (version_compare($plugin->version, $dependency['version']) >= 0) {
                $requiresUpdate = false;

                if ($plugin->isInstalled && $plugin->isEnabled) {
                    $isDependencyMissing = false;
                }
            }
        }

        $dependency['isMissing'] = $isDependencyMissing;
        $dependency['requiresUpdate'] = $requiresUpdate;
        $dependency['plugin'] = $plugin;

        return $dependency;
    }
}