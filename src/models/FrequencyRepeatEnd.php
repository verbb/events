<?php
namespace verbb\events\models;

use Craft;
use craft\base\Model;
use craft\validators\DateTimeValidator;

use DateTime;

class FrequencyRepeatEnd extends Model
{
    // Constants
    // =========================================================================

    public const TYPE_UNTIL = 'until';
    public const TYPE_AFTER = 'after';


    // Static Methods
    // =========================================================================

    public static function getTypes(): array
    {
        return [self::TYPE_UNTIL, self::TYPE_AFTER];
    }

    public static function getTypeOptions(): array
    {
        return [
            ['label' => Craft::t('events', 'On Date'), 'value' => self::TYPE_UNTIL],
            ['label' => Craft::t('events', 'After'), 'value' => self::TYPE_AFTER],
        ];
    }


    // Properties
    // =========================================================================

    public ?string $type = null;
    public ?DateTime $date = null;
    public ?int $count = null;


    // Public Methods
    // =========================================================================

    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();
        $labels['date'] = Craft::t('events', 'Recurring Ends Date');
        $labels['count'] = Craft::t('events', 'Recurring Ends Count');

        return $labels;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['type'], 'required'];
        $rules[] = [['type'], 'in', 'range' => self::getTypes()];

        $rules[] = [['count'], 'required', 'when' => fn() => $this->type === self::TYPE_AFTER];
        $rules[] = [['date'], 'required', 'when' => fn() => $this->type === self::TYPE_UNTIL];

        $rules[] = [['count'], 'integer', 'min' => 1, 'when' => fn() => $this->type === self::TYPE_AFTER];
        $rules[] = [['date'], DateTimeValidator::class];

        return $rules;
    }

}
