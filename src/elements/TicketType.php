<?php
namespace verbb\events\elements;

use verbb\events\Events;
use verbb\events\elements\db\TicketTypeQuery;
use verbb\events\records\TicketTypeRecord;

use Craft;
use craft\base\Element;
use craft\behaviors\FieldLayoutBehavior;
use craft\db\Query;
use craft\elements\actions\Delete;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\validators\DateTimeValidator;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;

use yii\base\Exception;

class TicketType extends Element
{
    // Static
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('events', 'Ticket Type');
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return true;
    }

    public static function find(): ElementQueryInterface
    {
        return new TicketTypeQuery(static::class);
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [[
            'key' => '*',
            'label' => Craft::t('events', 'All ticket types'),
        ]];

        return $sources;
    }


    // Element index methods
    // -------------------------------------------------------------------------

    protected static function defineSortOptions(): array
    {
        return [
            'id' => Craft::t('app', 'ID'),
            'title' => Craft::t('app', 'Title'),
        ];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Title')],
        ];
    }


    // Properties
    // =========================================================================

    public $id;
    public $handle;
    public $taxCategoryId;
    public $shippingCategoryId;
    public $fieldLayoutId;


    // Public Methods
    // =========================================================================

    public function rules()
    {
        $rules = parent::rules();

        $rules[] = [['handle'], 'required'];
        $rules[] = [['handle'], 'string', 'max' => 255];
        $rules[] = [['handle'], UniqueValidator::class, 'targetClass' => TicketTypeRecord::class, 'targetAttribute' => ['handle'], 'message' => 'Not Unique'];
        $rules[] = [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']];

        return $rules;
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('events/ticket-types/' . $this->id);
    }

    public function getFieldLayout()
    {
        $behavior = $this->getBehavior('fieldLayout');
        return $behavior->getFieldLayout();
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['fieldLayout'] = [
            'class' => FieldLayoutBehavior::class,
            'elementType' => Ticket::class,
            'idAttribute' => 'fieldLayoutId',
        ];

        return $behaviors;
    }

    public function getName()
    {
        return $this->title ?? '';
    }

    public function setName($value)
    {
        $this->title = $value;
    }


    // Events
    // -------------------------------------------------------------------------

    public function afterSave(bool $isNew)
    {
        if (!$isNew) {
            $ticketTypeRecord = TicketTypeRecord::findOne($this->id);

            if (!$ticketTypeRecord) {
                throw new Exception('Invalid ticket type id: ' . $this->id);
            }
        } else {
            $ticketTypeRecord = new TicketTypeRecord();
            $ticketTypeRecord->id = $this->id;
        }
        
        $ticketTypeRecord->handle = $this->handle;
        $ticketTypeRecord->taxCategoryId = $this->taxCategoryId;
        $ticketTypeRecord->shippingCategoryId = $this->shippingCategoryId;

        // Save the new one
        $fieldLayout = $this->getFieldLayout();
        Craft::$app->getFields()->saveLayout($fieldLayout, false);
        $this->fieldLayoutId = $fieldLayout->id;
        $ticketTypeRecord->fieldLayoutId = $fieldLayout->id;

        $ticketTypeRecord->save(false);

        return parent::afterSave($isNew);
    }

    public function beforeDelete(): bool
    {
        if (!parent::beforeDelete()) {
            return false;
        }

        $tickets = Ticket::find()
            ->typeId($this->id)
            ->anyStatus()
            ->limit(null)
            ->all();

        foreach ($tickets as $ticket) {
            Craft::$app->getElements()->deleteElement($ticket);
        }

        return true;
    }
}