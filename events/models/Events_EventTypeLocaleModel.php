<?php

namespace Craft;

/**
 * @property int    id
 * @property int    eventTypeId
 * @property string locale
 * @property string urlFormat
 */
class Events_EventTypeLocaleModel extends BaseModel
{
    // Properties
    // =========================================================================

    public $urlFormatIsRequired = true;

    // Public Methods
    // =========================================================================

    public function rules()
    {
        $rules = parent::rules();

        if ($this->urlFormatIsRequired) {
            $rules[] = ['urlFormat', 'required'];
        }

        return $rules;
    }

    // Protected Methods
    // =========================================================================

    protected function defineAttributes()
    {
        return [
            'id'          => AttributeType::Number,
            'eventTypeId' => AttributeType::Number,
            'locale'      => AttributeType::Locale,
            'urlFormat'   => [AttributeType::UrlFormat, 'label' => 'URL Format'],
        ];
    }
}
