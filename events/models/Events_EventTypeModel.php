<?php

namespace Craft;

/**
 * @property int    id
 * @property int    fieldLayoutId
 * @property string name
 * @property string handle
 * @property bool   hasUrls
 * @property bool   urlFormat
 * @property string template
 */
class Events_EventTypeModel extends BaseModel
{

    // Properties
    // =========================================================================

    private $_locales;


    // Public Methods
    // =========================================================================

    public function __toString()
    {
        return (string)Craft::t($this->handle);
    }

    public function getCpEditUrl()
    {
        return UrlHelper::getCpUrl('events/eventtypes/' . $this->id);
    }

    public function getLocales()
    {
        if (!isset($this->_locales)) {
            if ($this->id) {
                $this->_locales = EventsHelper::getEventTypesService()->getEventTypeLocales($this->id, 'locale');
            } else {
                $this->_locales = [];
            }
        }

        return $this->_locales;
    }

    public function setLocales($locales)
    {
        $this->_locales = $locales;
    }

    public function behaviors()
    {
        return [
            'eventFieldLayout' => new FieldLayoutBehavior('Events_Event', 'fieldLayoutId'),
        ];
    }

    // Protected Methods
    // =========================================================================

    protected function defineAttributes()
    {
        return [
            'id'            => AttributeType::Number,
            'fieldLayoutId' => AttributeType::Number,
            'name'          => AttributeType::Name,
            'handle'        => AttributeType::Handle,
//            'taxCategoryId' => AttributeType::Number,
            'hasUrls'       => AttributeType::Bool,
            'urlFormat'     => AttributeType::String,
//            'skuFormat' => AttributeType::String,
            'template'      => AttributeType::Template,
        ];
    }
}
