<?php
namespace verbb\events\base;

use verbb\events\elements\Session;
use verbb\events\models\FrequencyRepeatEnd;

use Craft;
use craft\base\Component;
use craft\base\ElementInterface;

use DateTime;

abstract class Frequency extends Component implements FrequencyInterface
{
    // Static Methods
    // =========================================================================

    public static function isRecurring(): bool
    {
        return false;
    }


    // Properties
    // =========================================================================

    public int $repeatCount = 1;
    public FrequencyRepeatEnd $repeatEnd;


    // Abstract Methods
    // =========================================================================

    abstract public static function id(): string;


    // Public Methods
    // =========================================================================

    public function __construct(array $config = [])
    {
        // Ensure that `repeatEnd` is always typecast properly
        if (!array_key_exists('repeatEnd', $config) || !($config['repeatEnd'] instanceof FrequencyRepeatEnd)) {
            $config['repeatEnd'] = new FrequencyRepeatEnd($config['repeatEnd'] ?? []);
        }

        parent::__construct($config);
    }

    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();
        $labels['type'] = Craft::t('events', 'Frequency');
        $labels['repeatCount'] = Craft::t('events', 'Repeat Every');
        $labels['repeatDay'] = Craft::t('events', 'Repeat On');
        $labels['repeatEnd'] = Craft::t('events', 'Recurring Ends');

        return $labels;
    }

    public function getInputHtml(ElementInterface $element = null): string
    {
        return '';
    }

    public function getRecurringSessionDates(Session $session): array
    {
        $dates = [];

        // Clone the dates for the session so we don't modify them permanently
        $sessionStartDate = clone $session->startDate;
        $sessionEndDate = clone $session->endDate;

        // We should keep track of how many sessions we've handled
        $occurrences = 0;

        while (true) {
            // Clone start/end dates for each iteration, rather than modify the source
            $startDate = (clone $sessionStartDate);
            $endDate = (clone $sessionEndDate);

            // Classes implement the next date logic
            $this->setNextRecurringDate($startDate, $endDate, $occurrences);

            // Check if this start date falls outside of the maxiumum repeat end we've set
            if (!$this->shouldContinueGenerating($startDate, $endDate, $occurrences)) {
                break;
            }

            $dates[] = [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ];

            $occurrences++;
        }

        // Remove the first occurrence as it should not be included, we just want the dates non-inclusive
        array_shift($dates);

        return $dates;
    }

    public function setNextRecurringDate(DateTime &$startDate, DateTime &$endDate, int $occurrences): void
    {
        return;
    }

    public function shouldContinueGenerating(DateTime $startDate, DateTime $endDate, int $occurrences): bool
    {
        if ($this->repeatEnd->type === 'after') {
            return $occurrences < $this->repeatEnd->count;
        } else if ($this->repeatEnd->type === 'until') {
            // Ensure that time doesn't factor into dates, just in case
            $repeatEndDate = (clone $this->repeatEnd->date)->modify('t 00:00:00');
            $startDate = (clone $startDate)->modify('t 00:00:00');

            return $startDate <= $repeatEndDate;
        }

        return false;
    }


    // Protected Methods
    // =========================================================================

    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['repeatCount'], 'required'];
        $rules[] = [['repeatCount'], 'integer', 'min' => 1];

        $rules[] = [
            ['repeatEnd'], function($model) {
                if (!$this->repeatEnd->validate()) {
                    foreach ($this->repeatEnd->getErrors() as $key => $errors) {
                        foreach ($errors as $error) {
                            $this->addError('repeatEnd', $error);
                        }
                    }
                }
            }
        ];

        return $rules;
    }

}