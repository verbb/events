<?php
namespace verbb\events\frequencies;

use verbb\events\base\Frequency;
use verbb\events\models\FrequencyRepeatEnd;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\helpers\Html;

use DateTime;

class Weekly extends Frequency
{
    // Static Methods
    // =========================================================================

    public static function id(): string
    {
        return 'weekly';
    }

    public static function displayName(): string
    {
        return Craft::t('events', 'Weekly');
    }

    public static function isRecurring(): bool
    {
        return true;
    }


    // Properties
    // =========================================================================

    public array $repeatDays = [];


    // Public Methods
    // =========================================================================

    public function setNextRecurringDate(DateTime $startDate, DateTime &$endDate, int $occurrences): void
    {
        // Convert repeat days to numeric values (0 = Monday, ..., 6 = Sunday)
        $dayMap = ['sunday' => 0, 'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4, 'friday' => 5, 'saturday' => 6];
        $repeatDaysNumeric = array_map(fn($day) => $dayMap[strtolower($day)], $this->repeatDays);
        sort($repeatDaysNumeric);

        // Calculate week offset
        $weekOffset = floor($occurrences / count($repeatDaysNumeric)) * $this->repeatCount;

        // Calculate day offset within the current week
        $dayIndex = $occurrences % count($repeatDaysNumeric);
        $targetDayOfWeek = $repeatDaysNumeric[$dayIndex];

        // Get the current day of week of the start date
        $startDayOfWeek = (int)$startDate->format('w');

        // Calculate the number of days to add to reach the target day of the week
        $dayOffset = $targetDayOfWeek - $startDayOfWeek;

        if ($dayOffset < 0) {
            $dayOffset += 7; // Wrap to the next week
        }

        // Combine week offset and day offset to generate the full date modify string
        $dateModify = '+' . ($weekOffset * 7 + $dayOffset) . ' days';

        // Rather than calculate twice, record the difference between start/end date so we can apply it after changing the start date
        $interval = $startDate->diff($endDate);

        $startDate->modify($dateModify);

        // Update the end date to reflect the same diff now that startDate has moved
        $endDate = (clone $startDate)->add($interval);
    }

    public function getInputHtml(ElementInterface $element = null): string
    {
        $html = [];

        $html[] = Cp::fieldHtml(Html::beginTag('div', ['class' => 'flex']) . 
            Html::beginTag('div') . 
                Cp::textHtml([
                    'name' => 'frequencyData[weekly][repeatCount]',
                    'value' => $this->repeatCount,
                    'type' => 'number',
                    'inputAttributes' => [
                        'style' => ['width' => '5rem'],
                    ],
                    'min' => 1,
                ]) . 
            Html::endTag('div') . 
            Html::tag('div', 'Week', ['aria-hidden' => 'true']) . 
        Html::endTag('div'), [
            'label' => Craft::t('events', 'Repeat Every'),
            'instructions' => Craft::t('events', 'Select how many weeks to repeat this session for.'),
            'id' => 'frequency-data-weekly-repeat-count',
            'fieldAttributes' => [
                'data-error-key' => 'frequencyData.weekly.repeatCount',
            ],
        ]);

        $html[] = Cp::checkboxGroupFieldHtml([
            'label' => Craft::t('events', 'Repeat On'),
            'instructions' => Craft::t('events', 'Select which day to repeat this session for.'),
            'name' => 'frequencyData[weekly][repeatDays]',
            'values' => $this->repeatDays,
            'options' => [
                ['label' => Craft::t('events', 'Sunday'), 'value' => 'sunday'],
                ['label' => Craft::t('events', 'Monday'), 'value' => 'monday'],
                ['label' => Craft::t('events', 'Tuesday'), 'value' => 'tuesday'],
                ['label' => Craft::t('events', 'Wednesday'), 'value' => 'wednesday'],
                ['label' => Craft::t('events', 'Thursday'), 'value' => 'thursday'],
                ['label' => Craft::t('events', 'Friday'), 'value' => 'friday'],
                ['label' => Craft::t('events', 'Saturday'), 'value' => 'saturday'],
            ],
            'id' => 'frequency-data-weekly-repeat-days',
            'fieldAttributes' => [
                'data-error-key' => 'frequencyData.weekly.repeatDays',
            ],
        ]);

        $repeatEndType = $this->repeatEnd->type;

        $html[] = Cp::fieldHtml(Html::beginTag('div', ['class' => 'flex']) . 
            Html::beginTag('div') . 
                Cp::selectHtml([
                    'name' => 'frequencyData[weekly][repeatEnd][type]',
                    'value' => $repeatEndType,
                    'toggle' => true,
                    'targetPrefix' => '.weekly-repeat-end-',
                    'options' => FrequencyRepeatEnd::getTypeOptions(),
                ]) . 
            Html::endTag('div') . 
            Html::beginTag('div', ['class' => ['flex weekly-repeat-end-until', ($repeatEndType !== FrequencyRepeatEnd::TYPE_UNTIL ? 'hidden' : '')]]) . 
                Cp::dateHtml([
                    'name' => 'frequencyData[weekly][repeatEnd][date]',
                    'value' => $this->repeatEnd->date,
                ]) .
            Html::endTag('div') .
            Html::beginTag('div', ['class' => ['flex weekly-repeat-end-after', ($repeatEndType !== FrequencyRepeatEnd::TYPE_AFTER ? 'hidden' : '')]]) . 
                Html::beginTag('div') . 
                    Cp::textHtml([
                        'name' => 'frequencyData[weekly][repeatEnd][count]',
                        'value' => $this->repeatEnd->count,
                        'type' => 'number',
                        'min' => 1,
                    ]) . 
                Html::endTag('div') . 
                Html::tag('div', 'occurrences', ['aria-hidden' => 'true']) . 
            Html::endTag('div') . 
        Html::endTag('div'), [
            'label' => Craft::t('events', 'Recurring Ends'),
            'instructions' => Craft::t('events', 'Select how long to repeat this session for.'),
            'id' => 'frequency-data-weekly-repeat-end',
            'fieldAttributes' => [
                'data-error-key' => 'frequencyData.weekly.repeatEnd',
            ],
        ]);

        return implode('', $html);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['repeatDays'], 'required'];

        return $rules;
    }
}