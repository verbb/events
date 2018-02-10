<?php
namespace Craft;

class Events_PluginController extends BaseController
{
    // Public Methods
    // =========================================================================

    /**
     * @throws HttpException
     */
    public function actionCheckRequirements()
    {
        $dependencies = EventsHelper::getPluginService()->checkRequirements();

        if ($dependencies) {
            $this->renderTemplate('events/dependencies', [
                'dependencies' => $dependencies,
            ]);
        }
    }

    /**
     * @throws HttpException
     */
    public function actionSettings()
    {
        $this->renderTemplate('events/settings/general', array(
            'settings' => EventsHelper::getPlugin()->getSettings()
        ));
    }
}