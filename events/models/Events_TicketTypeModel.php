<?php

namespace Craft;

/**
 * @property int    id
 * @property int    taxCategoryId
 * @property int    shippingCategoryId
 * @property int    fieldLayoutId
 * @property string handle
 */
class Events_TicketTypeModel extends BaseElementModel
{
    // Properties
    // =========================================================================

//    private $_locales;
    protected $elementType = 'Events_TicketType';


    // Public Methods
    // =========================================================================

//    public function __toString()
//    {
//        return (string)Craft::t($this->handle);
//    }

    public function getCpEditUrl()
    {
        return UrlHelper::getCpUrl('events/tickettypes/' . $this->id);
    }

//    public function getLocales()
//    {
//        if (!isset($this->_locales)) {
//            if ($this->id) {
//                $this->_locales = craft()->events_ticketTypes->getTicketTypeLocales($this->id, 'locale');
//            } else {
//                $this->_locales = [];
//            }
//        }
//
//        return $this->_locales;
//    }
//
//    public function setLocales($locales)
//    {
//        $this->_locales = $locales;
//    }

    public function isEditable()
    {
        return EventsHelper::getLicenseService()->isLicensed();
    }

    public function hasTitles()
    {
        return true;
    }

    public function behaviors()
    {
        return [
            'ticketFieldLayout' => new FieldLayoutBehavior('Events_Ticket', 'fieldLayoutId'),
        ];
    }

    // Protected Methods
    // =========================================================================

    protected function defineAttributes()
    {
        return array_merge(parent::defineAttributes(), [
            'id'            => AttributeType::Number,
            'taxCategoryId' => AttributeType::Number,
            'shippingCategoryId' => AttributeType::Number,
            'fieldLayoutId' => AttributeType::Number,
            //'name' => AttributeType::Name,
            'handle'        => AttributeType::Handle,
//            'skuFormat' => AttributeType::String,
//            'hasUrls'       => AttributeType::Bool,
//            'urlFormat'     => AttributeType::String,
//            'template'      => AttributeType::Template,
        ]);
    }
}
