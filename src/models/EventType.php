<?php
namespace verbb\events\models;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\records\EventType as EventTypeRecord;

use Craft;
use craft\base\Model;
use craft\behaviors\FieldLayoutBehavior;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

use Exception;

class EventType extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?string $name = null;
    public ?string $handle = null;
    public ?int $fieldLayoutId = null;
    public bool $hasTitleField = true;
    public string $titleLabel = 'Title';
    public ?string $titleFormat = null;
    public bool $hasTickets = true;
    public ?string $icsTimezone = null;
    public ?string $icsDescriptionFieldHandle = null;
    public ?string $icsLocationFieldHandle = null;
    public ?string $uid = null;

    private ?array $_siteSettings = null;


    // Public Methods
    // =========================================================================

    public function __toString(): string
    {
        return $this->handle;
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['eventFieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => Event::class,
            'idAttribute' => 'fieldLayoutId',
        ];

        return $behaviors;
    }

    public function attributeLabels(): array
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
            'titleFormat' => Craft::t('app', 'Title Format'),
            'titleLabel' => Craft::t('app', 'Title Field Label'),
        ];
    }

    public function rules(): array
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

    public function setSiteSettings(array $siteSettings): void
    {
        $this->_siteSettings = $siteSettings;

        foreach ($this->_siteSettings as $settings) {
            $settings->setEventType($this);
        }
    }

    public function getEventFieldLayout(): ?FieldLayout
    {
        return $this->getBehavior('eventFieldLayout')->getFieldLayout();
    }

    public function getEventFieldHandles(): array
    {
        $fieldList = [
            [
                'label' => Craft::t('events', 'None'),
                'value' => '',
            ],
        ];

        if ($this->getFieldLayout()) {
            foreach ($this->getFieldLayout()->getCustomFields() as $field) {
                $fieldList[$field->handle] = $field->name;
            }
        }

        return $fieldList;
    }

    public function getIcsUrl(): string
    {
        return UrlHelper::actionUrl('events/ics/event-type', ['typeId' => $this->id]);
    }

    public function getConfig(): array
    {
        $config = [
            'name' => $this->name,
            'handle' => $this->handle,
            'hasTitleField' => $this->hasTitleField,
            'titleLabel' => $this->titleLabel,
            'titleFormat' => $this->titleFormat,
            'hasTickets' => $this->hasTickets,
            'icsTimezone' => $this->icsTimezone,
            'icsDescriptionFieldHandle' => $this->icsDescriptionFieldHandle,
            'icsLocationFieldHandle' => $this->icsLocationFieldHandle,
            'siteSettings' => [],
        ];

        $generateLayoutConfig = function(FieldLayout $fieldLayout): array {
            $fieldLayoutConfig = $fieldLayout->getConfig();

            if ($fieldLayoutConfig) {
                if (empty($fieldLayout->id)) {
                    $layoutUid = StringHelper::UUID();
                    $fieldLayout->uid = $layoutUid;
                } else {
                    $layoutUid = Db::uidById('{{%fieldlayouts}}', $fieldLayout->id);
                }

                return [$layoutUid => $fieldLayoutConfig];
            }

            return [];
        };

        $config['eventFieldLayouts'] = $generateLayoutConfig($this->getFieldLayout());

        // Get the site settings
        $allSiteSettings = $this->getSiteSettings();

        // Make sure they're all there
        foreach (Craft::$app->getSites()->getAllSiteIds() as $siteId) {
            if (!isset($allSiteSettings[$siteId])) {
                throw new Exception('Tried to save a event type that is missing site settings');
            }
        }

        foreach ($allSiteSettings as $siteId => $settings) {
            $siteUid = Db::uidById('{{%sites}}', $siteId);
            $config['siteSettings'][$siteUid] = [
                'hasUrls' => $settings['hasUrls'],
                'uriFormat' => $settings['uriFormat'],
                'template' => $settings['template'],
            ];
        }

        return $config;
    }
}
