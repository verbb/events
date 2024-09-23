<?php
namespace verbb\events\frequencies;

use verbb\events\base\Frequency;
use verbb\events\models\FrequencyRepeatEnd;

use Craft;
use craft\base\ElementInterface;
use craft\helpers\Cp;
use craft\helpers\Html;

use DateTime;

class Daily extends Frequency
{
    // Static Methods
    // =========================================================================

    public static function id(): string
    {
        return 'daily';
    }

    public static function displayName(): string
    {
        return Craft::t('events', 'Daily');
    }

    public static function isRecurring(): bool
    {
        return true;
    }


    // Public Methods
    // =========================================================================

    public function setNextRecurringDate(DateTime $startDate, DateTime $endDate, int $occurrences): void
    {
        $dateModify = '+' . ($occurrences * $this->repeatCount) . ' days';

        $startDate->modify($dateModify);
        $endDate->modify($dateModify);
    }

    public function getInputHtml(ElementInterface $element = null): string
    {
        $html = [];

        $html[] = Cp::fieldHtml(Html::beginTag('div', ['class' => 'flex']) . 
            Html::beginTag('div') . 
                Cp::textHtml([
                    'name' => 'frequencyData[daily][repeatCount]',
                    'value' => $this->repeatCount,
                    'type' => 'number',
                    'inputAttributes' => [
                        'style' => ['width' => '5rem'],
                    ],
                    'min' => 1,
                    'max' => 7,
                ]) . 
            Html::endTag('div') . 
            Html::tag('div', 'Day', ['aria-hidden' => 'true']) . 
        Html::endTag('div'), [
            'label' => Craft::t('events', 'Repeat Every'),
            'instructions' => Craft::t('events', 'Select how many days to repeat this session for.'),
            'id' => 'frequency-data-daily-repeat-count',
            'fieldAttributes' => [
                'data-error-key' => 'frequencyData.daily.repeatCount',
            ],
        ]);

        $repeatEndType = $this->repeatEnd->type;

        $html[] = Cp::fieldHtml(Html::beginTag('div', ['class' => 'flex']) . 
            Html::beginTag('div') . 
                Cp::selectHtml([
                    'id' => 'frequency-data-daily-repeat-end-type',
                    'name' => 'frequencyData[daily][repeatEnd][type]',
                    'value' => $repeatEndType,
                    'toggle' => true,
                    'targetPrefix' => '.daily-repeat-end-',
                    'options' => FrequencyRepeatEnd::getTypeOptions(),
                ]) . 
            Html::endTag('div') . 
            Html::beginTag('div', ['class' => ['flex daily-repeat-end-until', ($repeatEndType !== FrequencyRepeatEnd::TYPE_UNTIL ? 'hidden' : '')]]) . 
                Cp::dateHtml([
                    'name' => 'frequencyData[daily][repeatEnd][date]',
                    'value' => $this->repeatEnd->date,
                ]) .
            Html::endTag('div') .
            Html::beginTag('div', ['class' => ['flex daily-repeat-end-after', ($repeatEndType !== FrequencyRepeatEnd::TYPE_AFTER ? 'hidden' : '')]]) . 
                Html::beginTag('div') . 
                    Cp::textHtml([
                        'name' => 'frequencyData[daily][repeatEnd][count]',
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
            'id' => 'frequency-data-daily-repeat-end',
            'fieldAttributes' => [
                'data-error-key' => 'frequencyData.daily.repeatEnd',
            ],
        ]);

        return implode('', $html);
    }
}