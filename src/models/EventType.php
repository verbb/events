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
    public $hasTitleField = true;
    public $titleLabel = 'Title';
    public $titleFormat;
    public $hasTickets = true;
    public $uid;

    private $_siteSettings;


    // Public Methods
    // =========================================================================

    public function __toString()
    {
        return $this->handle;
    }

    public function behaviors()
    {
        return [
            'eventFieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => Event::class,
                'idAttribute' => 'fieldLayoutId'
            ]
        ];
    }

    public function attributeLabels()
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
            'titleFormat' => Craft::t('app', 'Title Format'),
            'titleLabel' => Craft::t('app', 'Title Field Label'),
        ];
    }

    public function rules()
    {
        $rules = parent::rules();

        $rules[] = [['id', 'fieldLayoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['name', 'handle'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];

        $rules[] = [
            ['handle'],
            UniqueValidator::class,
            'targetClass' => EventTypeRecord::class,
            'targetAttribute' => ['handle'],
            'message' => 'Not Unique',
        ];

        $rules[] = [
            ['handle'],
            HandleValidator::class,
            'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title'],
        ];

        if ($this->hasTitleField) {
            $rules[] = [['titleLabel'], 'required'];
        } else {
            $rules[] = [['titleFormat'], 'required'];
        }

        return $rules;
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
}
