<?php
namespace verbb\events\models;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\records\EventTypeRecord;

use Craft;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

class EventType extends Model
{
    // Properties
    // =========================================================================

    public $id;
    public $name;
    public $handle;
    public $fieldLayoutId;
    public $uid;

    private $_siteSettings;


    // Public Methods
    // =========================================================================

    public function __toString()
    {
        return $this->handle;
    }

    public function rules()
    {
        return [
            [['id', 'fieldLayoutId'], 'number', 'integerOnly' => true],
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255],
            [['handle'], UniqueValidator::class, 'targetClass' => EventTypeRecord::class, 'targetAttribute' => ['handle'], 'message' => 'Not Unique'],
            [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
        ];
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('events/event-types/' . $this->id);
    }

    public function getSiteSettings(): array
    {
        if ($this->_siteSettings !== null) {
            return $this->_siteSettings;
        }

        if (!$this->id) {
            return [];
        }

        $this->setSiteSettings(ArrayHelper::index(Events::$plugin->getEventTypes()->getEventTypeSites($this->id), 'siteId'));

        return $this->_siteSettings;
    }

    public function setSiteSettings(array $siteSettings)
    {
        $this->_siteSettings = $siteSettings;

        foreach ($this->_siteSettings as $settings) {
            $settings->setEventType($this);
        }
    }

    public function getEventFieldLayout(): FieldLayout
    {
        $behavior = $this->getBehavior('eventFieldLayout');
        return $behavior->getFieldLayout();
    }

    public function behaviors(): array
    {
        return [
            'eventFieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => Event::class,
                'idAttribute' => 'fieldLayoutId'
            ]
        ];
    }
}
