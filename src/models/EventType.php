<?php
namespace verbb\events\models;

use verbb\events\Events;
use verbb\events\elements\Event;
use verbb\events\elements\Session;
use verbb\events\elements\TicketType;
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

use DateTime;
use DateTimeZone;

class EventType extends Model
{
    // Properties
    // =========================================================================

    public ?int $id = null;
    public ?string $name = null;
    public ?string $handle = null;
    public ?int $fieldLayoutId = null;
    public ?int $sessionFieldLayoutId = null;
    public ?int $ticketTypeFieldLayoutId = null;
    public bool $enableVersioning = false;
    public string $sessionTitleFormat = '{dateSummary}';
    public string $ticketTitleFormat = '{type.title} - {session.title}';
    public string $ticketSkuFormat = '';
    public string $purchasedTicketTitleFormat = '{event.title} - {ticket.title}';
    public ?string $icsTimezone = null;
    public ?string $icsDescriptionFieldHandle = null;
    public ?string $icsLocationFieldHandle = null;
    public ?string $uid = null;

    private ?array $_siteSettings = null;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        unset($config['hasTitleField'], $config['titleLabel'], $config['titleFormat']);

        parent::__construct($config);
    }

    public function __toString(): string
    {
        return $this->handle;
    }

    public function getCpEditUrl(): ?string
    {
        return UrlHelper::cpUrl('events/event-types/' . $this->id);
    }

    public function getCpEditSessionUrl(): string
    {
        return UrlHelper::cpUrl('events/event-types/' . $this->id . '/session');
    }

    public function getCpEditTicketUrl(): string
    {
        return UrlHelper::cpUrl('events/event-types/' . $this->id . '/ticket');
    }

    public function attributeLabels(): array
    {
        return [
            'handle' => Craft::t('app', 'Handle'),
            'name' => Craft::t('app', 'Name'),
        ];
    }

    public function getSiteIds(): array
    {
        return array_keys($this->getSiteSettings());
    }

    public function getSiteSettings(): array
    {
        if (isset($this->_siteSettings)) {
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
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('eventFieldLayout');
        return $behavior->getFieldLayout();
    }

    public function validateFieldLayout(): void
    {
        $fieldLayout = $this->getFieldLayout();

        $fieldLayout->reservedFieldHandles = [
            'sessions',
            'tickets',
        ];

        if (!$fieldLayout->validate()) {
            $this->addModelErrors($fieldLayout, 'fieldLayout');
        }
    }

    public function validateSessionFieldLayout(): void
    {
        $sessionFieldLayout = $this->getSessionFieldLayout();

        if (!$sessionFieldLayout->validate()) {
            $this->addModelErrors($sessionFieldLayout, 'sessionFieldLayout');
        }
    }

    public function getSessionFieldLayout(): FieldLayout
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('sessionFieldLayout');
        
        return $behavior->getFieldLayout();
    }

    public function validateTicketFieldLayout(): void
    {
        $ticketFieldLayout = $this->getTicketTypeFieldLayout();

        if (!$ticketFieldLayout->validate()) {
            $this->addModelErrors($ticketFieldLayout, 'ticketFieldLayout');
        }
    }

    public function getTicketTypeFieldLayout(): FieldLayout
    {
        /** @var FieldLayoutBehavior $behavior */
        $behavior = $this->getBehavior('ticketFieldLayout');
        
        return $behavior->getFieldLayout();
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
            'enableVersioning' => $this->enableVersioning,
            'sessionTitleFormat' => $this->sessionTitleFormat,
            'ticketTitleFormat' => $this->ticketTitleFormat,
            'ticketSkuFormat' => $this->ticketSkuFormat,
            'purchasedTicketTitleFormat' => $this->purchasedTicketTitleFormat,
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
        $config['sessionFieldLayouts'] = $generateLayoutConfig($this->getSessionFieldLayout());
        $config['ticketFieldLayouts'] = $generateLayoutConfig($this->getTicketTypeFieldLayout());

        // Get the site settings
        foreach ($this->getSiteSettings() as $siteId => $settings) {
            $siteUid = Db::uidById('{{%sites}}', $siteId);
            $config['siteSettings'][$siteUid] = [
                'hasUrls' => $settings['hasUrls'],
                'enabledByDefault' => $settings['enabledByDefault'],
                'uriFormat' => $settings['uriFormat'],
                'template' => $settings['template'],
            ];
        }

        return $config;
    }

    public function getTimezoneOptions(): array
    {
        // Assemble the timezone options array (Technique adapted from http://stackoverflow.com/a/7022536/1688568)
        $timezoneOptions = [];

        $utc = new DateTime();
        $offsets = [];
        $timezoneIds = [];

        foreach (DateTimeZone::listIdentifiers() as $timezoneId) {
            $timezone = new DateTimeZone($timezoneId);
            $transition = $timezone->getTransitions($utc->getTimestamp(), $utc->getTimestamp());
            $abbr = $transition[0]['abbr'];

            $offset = round($timezone->getOffset($utc) / 60);

            if ($offset) {
                $hour = floor($offset / 60);
                $minutes = floor(abs($offset) % 60);

                $format = sprintf('%+d', $hour);

                if ($minutes) {
                    $format .= ':' . sprintf('%02u', $minutes);
                }
            } else {
                $format = '';
            }

            $offsets[] = $offset;
            $timezoneIds[] = $timezoneId;
            $timezoneOptions[] = [
                'value' => $timezoneId,
                'label' => 'UTC' . $format . ($abbr !== 'UTC' ? " ({$abbr})" : '') . ($timezoneId !== 'UTC' ? ' – ' . $timezoneId : ''),
            ];
        }

        array_multisort($offsets, $timezoneIds, $timezoneOptions);

        $appended[] = [
            'value' => '',
            'label' => Craft::t('events', 'Floating Timezone (recommended)'),
        ];

        return array_merge($appended, $timezoneOptions);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['id', 'fieldLayoutId', 'sessionFieldLayoutId', 'ticketTypeFieldLayoutId'], 'number', 'integerOnly' => true];
        $rules[] = [['name', 'handle', 'sessionTitleFormat', 'ticketTitleFormat','purchasedTicketTitleFormat'], 'required'];
        $rules[] = [['name', 'handle'], 'string', 'max' => 255];

        $rules[] = [['handle'], UniqueValidator::class, 'targetClass' => EventTypeRecord::class, 'targetAttribute' => ['handle'], 'message' => 'Not Unique'];
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];

        $rules[] = ['fieldLayout', 'validateFieldLayout'];
        $rules[] = ['sessionFieldLayoutId', 'validateSessionFieldLayout'];
        $rules[] = ['ticketTypeFieldLayoutId', 'validateTicketFieldLayout'];

        return $rules;
    }

    protected function defineBehaviors(): array
    {
        $behaviors = parent::defineBehaviors();

        $behaviors['eventFieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => Event::class,
            'idAttribute' => 'fieldLayoutId',
        ];

        $behaviors['sessionFieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => Session::class,
            'idAttribute' => 'sessionFieldLayoutId',
        ];

        $behaviors['ticketFieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => TicketType::class,
            'idAttribute' => 'ticketTypeFieldLayoutId',
        ];

        return $behaviors;
    }
}
