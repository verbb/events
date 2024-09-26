<?php
namespace verbb\events\frequencies;

use verbb\events\base\Frequency;
use verbb\events\models\FrequencyRepeatEnd;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\helpers\Html;

use DateTime;

class Monthly extends Frequency
{
    // Static Methods
    // =========================================================================

    public static function id(): string
    {
        return 'monthly';
    }

    public static function displayName(): string
    {
        return Craft::t('events', 'Monthly');
    }

    public static function isRecurring(): bool
    {
        return true;
    }


    // Properties
    // =========================================================================

    public ?string $repeatDay = null;


    // Public Methods
    // =========================================================================

    public function setNextRecurringDate(DateTime &$startDate, DateTime &$endDate, int $occurrences): void
    {
        if ($this->repeatDay === 'onDate') {
            $dateModify = '+' . ($occurrences * $this->repeatCount) . ' months';

            // Rather than calculate twice, record the different between start/end date so we can apply it after changing the start date
            $interval = $startDate->diff($endDate);

            $startDate->modify($dateModify);
            
            // Update the end date to reflect the same diff now that startDate has moved
            $endDate = (clone $startDate)->add($interval);

            return;
        }

        // Get the current date's ordinal week value (e.g. `third monday`)
        $ordinalWeekDay = $this->_getOrdinalWeek($startDate);

        // e.g. `third monday of +2 months`
        $dateModify = $ordinalWeekDay . ' of +' . ($occurrences * $this->repeatCount) . ' months';

        // This modify will strip time off the date, so ensure we save and restore it
        $hours = (int)$startDate->format('H');
        $minutes = (int)$startDate->format('i');
        $seconds = (int)$startDate->format('s');

        // Rather than calculate twice, record the difference between start/end date so we can apply it after changing the start date
        $interval = $startDate->diff($endDate);

        $startDate->modify($dateModify)->setTime($hours, $minutes, $seconds);

        // Update the end date to reflect the same diff now that startDate has moved
        $endDate = (clone $startDate)->add($interval);
    }

    public function getInputHtml(ElementInterface $element = null): string
    {
        $html = [];

        $html[] = Cp::fieldHtml(Html::beginTag('div', ['class' => 'flex']) . 
            Html::beginTag('div') . 
                Cp::textHtml([
                    'name' => 'frequencyData[monthly][repeatCount]',
                    'value' => $this->repeatCount,
                    'type' => 'number',
                    'inputAttributes' => [
                        'style' => ['width' => '5rem'],
                    ],
                    'min' => 1,
                ]) . 
            Html::endTag('div') . 
            Html::tag('div', 'Month', ['aria-hidden' => 'true']) . 
        Html::endTag('div'), [
            'label' => Craft::t('events', 'Repeat Every'),
            'instructions' => Craft::t('events', 'Select how many months to repeat this session for.'),
            'id' => 'frequency-data-monthly-repeat-count',
            'fieldAttributes' => [
                'data-error-key' => 'frequencyData.monthly.repeatCount',
            ],
        ]);

        $html[] = Cp::selectFieldHtml([
            'label' => Craft::t('events', 'Repeat On'),
            'instructions' => Craft::t('events', 'Select which day to repeat this session for.'),
            'name' => 'frequencyData[monthly][repeatDay]',
            'value' => $this->repeatDay,
            'options' => [],
            'id' => 'frequency-data-monthly-repeat-day',
            'fieldAttributes' => [
                'data-error-key' => 'frequencyData.monthly.repeatDay',
            ],
        ]);

        $repeatEndType = $this->repeatEnd->type;

        $html[] = Cp::fieldHtml(Html::beginTag('div', ['class' => 'flex']) . 
            Html::beginTag('div') . 
                Cp::selectHtml([
                    'name' => 'frequencyData[monthly][repeatEnd][type]',
                    'value' => $repeatEndType,
                    'toggle' => true,
                    'targetPrefix' => '.monthly-repeat-end-',
                    'options' => FrequencyRepeatEnd::getTypeOptions(),
                ]) . 
            Html::endTag('div') . 
            Html::beginTag('div', ['class' => ['flex monthly-repeat-end-until', ($repeatEndType !== FrequencyRepeatEnd::TYPE_UNTIL ? 'hidden' : '')]]) . 
                Cp::dateHtml([
                    'name' => 'frequencyData[monthly][repeatEnd][date]',
                    'value' => $this->repeatEnd->date,
                ]) .
            Html::endTag('div') .
            Html::beginTag('div', ['class' => ['flex monthly-repeat-end-after', ($repeatEndType !== FrequencyRepeatEnd::TYPE_AFTER ? 'hidden' : '')]]) . 
                Html::beginTag('div') . 
                    Cp::textHtml([
                        'name' => 'frequencyData[monthly][repeatEnd][count]',
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
            'id' => 'frequency-data-monthly-repeat-end',
            'fieldAttributes' => [
                'data-error-key' => 'frequencyData.monthly.repeatEnd',
            ],
        ]);

        return implode('', $html);
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['repeatDay'], 'required'];

        return $rules;
    }


    // Private Methods
    // =========================================================================

    private function _getOrdinalWeek(DateTime $date): string
    {
        // Get the day of the month
        $day = $date->format('j');
        
        // Calculate the week number (1st, 2nd, etc.)
        $weekNumber = (int)floor(($day - 1) / 7) + 1;
        
        // Create an array of ordinal numbers
        $ordinals = ['first', 'second', 'third', 'fourth', 'fifth'];
        
        // Return the corresponding ordinal
        return $ordinals[$weekNumber - 1] . ' ' . strtolower($date->format('l'));
    }
}