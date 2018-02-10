<?php

namespace Craft;

/**
 * @property string requestUrl
 * @property string requestIp
 * @property string requestTime
 * @property string requestPort
 *
 * @property string craftBuild
 * @property string craftVersion
 * @property string craftEdition
 * @property string craftTrack
 * @property string userEmail
 *
 * @property string licenseKey
 * @property string licensedEdition
 * @property string requestProduct
 * @property string requestVersion
 * @property mixed  data
 * @property mixed  errors
 */
class Events_LicenseModel extends BaseModel
{
    // Public Methods
    // =========================================================================

    public function decode()
    {
        echo JsonHelper::decode($this);
    }


    // Protected Methods
    // =========================================================================

    protected function defineAttributes()
    {
        return [
            'requestUrl'  => [AttributeType::String],
            'requestIp'   => [AttributeType::String],
            'requestTime' => [AttributeType::String],
            'requestPort' => [AttributeType::String],

            'craftBuild'   => [AttributeType::String],
            'craftVersion' => [AttributeType::String],
            'craftEdition' => [AttributeType::String],
            'craftTrack'   => [AttributeType::String],
            'userEmail'    => [AttributeType::String],

            'licenseKey'      => [AttributeType::String],
            'licensedEdition' => [AttributeType::String],
            'requestProduct'  => [AttributeType::String],
            'requestVersion'  => [AttributeType::String],
            'data'            => [AttributeType::Mixed],
            'errors'          => [AttributeType::Mixed],
        ];
    }
}
