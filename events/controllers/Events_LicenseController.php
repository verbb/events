<?php
namespace Craft;

class Events_LicenseController extends BaseController
{

    // Public Methods
    // =========================================================================

    public function actionEdit()
    {
        $licenseKey = craft()->events_license->getLicenseKey();

        $this->renderTemplate('events/settings/license', [
            'hasLicenseKey' => ($licenseKey !== null)
        ]);
    }

    public function actionGetLicenseInfo()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        return $this->_sendResponse(craft()->events_license->getLicenseInfo());
    }

    public function actionUnregister()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        return $this->_sendResponse(craft()->events_license->unregisterLicenseKey());
    }

    public function actionTransfer()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        return $this->_sendResponse(craft()->events_license->transferLicenseKey());
    }

    public function actionUpdateLicenseKey()
    {
        $this->requirePostRequest();
        $this->requireAjaxRequest();

        $licenseKey = craft()->request->getRequiredPost('licenseKey');

        // Are we registering a new license key?
        if ($licenseKey) {
            // Record the license key locally
            try {
                craft()->events_license->setLicenseKey($licenseKey);
            } catch (InvalidLicenseKeyException $e) {
                $this->returnErrorJson(Craft::t('The license key is invalid.'));
            }

            return $this->_sendResponse(craft()->events_license->registerPlugin($licenseKey));
        } else {
            // Just clear our record of the license key
            craft()->events_license->setLicenseKey(null);
            craft()->events_license->setLicenseKeyStatus(LicenseKeyStatus::Unknown);
            return $this->_sendResponse();

        }
    }


    // Private Methods
    // =========================================================================

    private function _sendResponse($success = true)
    {
        if ($success) {
            $this->returnJson(array(
                'success'          => true,
                'licenseKey'       => craft()->events_license->getLicenseKey(),
                'licenseKeyStatus' => craft()->plugins->getPluginLicenseKeyStatus('Events'),
            ));
        } else {
            //$this->returnErrorJson(craft()->events_license->error);
            $this->returnErrorJson(Craft::t('An unknown error occurred.'));
        }
    }

}
