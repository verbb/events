<?php
namespace verbb\events\console\controllers;

use verbb\events\base\FrequencyInterface;
use verbb\events\elements\Session;
use verbb\events\frequencies\Daily;
use verbb\events\frequencies\Weekly;
use verbb\events\frequencies\Monthly;
use verbb\events\models\FrequencyRepeatEnd;

use craft\console\Controller;
use craft\helpers\Console;
use craft\helpers\StringHelper;

use yii\console\ExitCode;

use DateTime;
use ReflectionClass;
use ReflectionMethod;

class TestsController extends Controller
{
    // Properties
    // =========================================================================

    public ?FrequencyInterface $frequency = null;
    public ?DateTime $startDate = null;
    public ?DateTime $endDate = null;
    public array $expected = [];


    // Public Methods
    // =========================================================================

    public function actionIndex(): int
    {
        // Use reflection to get all methods in this class
        $reflector = new ReflectionClass($this);
        $methods = $reflector->getMethods(ReflectionMethod::IS_PROTECTED);

        // Loop through each method and find those starting with 'test'
        foreach ($methods as $method) {
            if (str_starts_with($method->name, 'test')) {
                $this->{$method->name}();
                $this->_recurringSessionDatesTest($method->name);
            }
        }

        return ExitCode::OK;
    }


    // Protected Methods
    // =========================================================================

    protected function testDaily(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Daily([
            'repeatCount' => 1,
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 4,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
            ['startDate' => new DateTime('2024-09-18 01:00:00'), 'endDate' => new DateTime('2024-09-18 03:00:00')],
            ['startDate' => new DateTime('2024-09-19 01:00:00'), 'endDate' => new DateTime('2024-09-19 03:00:00')],
        ];
    }

    protected function testDailyWithDifferentEndDate(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-18 03:00:00');

        $this->frequency = new Daily([
            'repeatCount' => 1,
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 4,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-19 03:00:00')],
            ['startDate' => new DateTime('2024-09-18 01:00:00'), 'endDate' => new DateTime('2024-09-20 03:00:00')],
            ['startDate' => new DateTime('2024-09-19 01:00:00'), 'endDate' => new DateTime('2024-09-21 03:00:00')],
        ];
    }

    // protected function testDailyWithDayOverlap(): void
    // {
    //     $this->startDate = new DateTime('2024-09-16 01:00:00');
    //     $this->endDate = new DateTime('2024-09-17 03:00:00');

    //     $this->frequency = new Daily([
    //         'repeatCount' => 1,
    //         'repeatEnd' => new FrequencyRepeatEnd([
    //             'type' => 'after',
    //             'count' => 4,
    //         ]),
    //     ]);

    //     $this->expected = [
    //         ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
    //         ['startDate' => new DateTime('2024-09-23 01:00:00'), 'endDate' => new DateTime('2024-09-23 03:00:00')],
    //         ['startDate' => new DateTime('2024-09-24 01:00:00'), 'endDate' => new DateTime('2024-09-24 03:00:00')],
    //     ];
    // }

    protected function testDailyWithDateEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Daily([
            'repeatCount' => 1,
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-09-20'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
            ['startDate' => new DateTime('2024-09-18 01:00:00'), 'endDate' => new DateTime('2024-09-18 03:00:00')],
            ['startDate' => new DateTime('2024-09-19 01:00:00'), 'endDate' => new DateTime('2024-09-19 03:00:00')],
            ['startDate' => new DateTime('2024-09-20 01:00:00'), 'endDate' => new DateTime('2024-09-20 03:00:00')],
        ];
    }

    protected function testDailyWithNumericEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Daily([
            'repeatCount' => 1,
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 5,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
            ['startDate' => new DateTime('2024-09-18 01:00:00'), 'endDate' => new DateTime('2024-09-18 03:00:00')],
            ['startDate' => new DateTime('2024-09-19 01:00:00'), 'endDate' => new DateTime('2024-09-19 03:00:00')],
            ['startDate' => new DateTime('2024-09-20 01:00:00'), 'endDate' => new DateTime('2024-09-20 03:00:00')],
        ];
    }

    protected function testDailyRepeatTwoWithDateEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Daily([
            'repeatCount' => 2,
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-09-20'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-18 01:00:00'), 'endDate' => new DateTime('2024-09-18 03:00:00')],
            ['startDate' => new DateTime('2024-09-20 01:00:00'), 'endDate' => new DateTime('2024-09-20 03:00:00')],
        ];
    }

    protected function testDailyRepeatTwoWithNumericEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Daily([
            'repeatCount' => 2,
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 5,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-18 01:00:00'), 'endDate' => new DateTime('2024-09-18 03:00:00')],
            ['startDate' => new DateTime('2024-09-20 01:00:00'), 'endDate' => new DateTime('2024-09-20 03:00:00')],
            ['startDate' => new DateTime('2024-09-22 01:00:00'), 'endDate' => new DateTime('2024-09-22 03:00:00')],
            ['startDate' => new DateTime('2024-09-24 01:00:00'), 'endDate' => new DateTime('2024-09-24 03:00:00')],
        ];
    }

    protected function testDailyRepeatThreeWithDateEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Daily([
            'repeatCount' => 3,
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-09-20'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-19 01:00:00'), 'endDate' => new DateTime('2024-09-19 03:00:00')],
        ];
    }

    protected function testDailyRepeatThreeWithNumericEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Daily([
            'repeatCount' => 3,
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 5,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-19 01:00:00'), 'endDate' => new DateTime('2024-09-19 03:00:00')],
            ['startDate' => new DateTime('2024-09-22 01:00:00'), 'endDate' => new DateTime('2024-09-22 03:00:00')],
            ['startDate' => new DateTime('2024-09-25 01:00:00'), 'endDate' => new DateTime('2024-09-25 03:00:00')],
            ['startDate' => new DateTime('2024-09-28 01:00:00'), 'endDate' => new DateTime('2024-09-28 03:00:00')],
        ];
    }

    protected function testDailyRepeatWithDateEndSameDate(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Daily([
            'repeatCount' => 3,
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-09-19'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-19 01:00:00'), 'endDate' => new DateTime('2024-09-19 03:00:00')],
        ];
    }

    protected function testDailyRepeatWithDateEndBeforeDate(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Daily([
            'repeatCount' => 3,
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-09-18'),
            ]),
        ]);

        $this->expected = [];
    }

    






    protected function testWeekly(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Weekly([
            'repeatCount' => 1,
            'repeatDays' => ['monday'],
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 4,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-23 01:00:00'), 'endDate' => new DateTime('2024-09-23 03:00:00')],
            ['startDate' => new DateTime('2024-09-30 01:00:00'), 'endDate' => new DateTime('2024-09-30 03:00:00')],
            ['startDate' => new DateTime('2024-10-07 01:00:00'), 'endDate' => new DateTime('2024-10-07 03:00:00')],
        ];
    }

    protected function testWeeklyWithDifferentEndDate(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-18 03:00:00');

        $this->frequency = new Weekly([
            'repeatCount' => 1,
            'repeatDays' => ['monday'],
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 4,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-23 01:00:00'), 'endDate' => new DateTime('2024-09-25 03:00:00')],
            ['startDate' => new DateTime('2024-09-30 01:00:00'), 'endDate' => new DateTime('2024-10-02 03:00:00')],
            ['startDate' => new DateTime('2024-10-07 01:00:00'), 'endDate' => new DateTime('2024-10-09 03:00:00')],
        ];
    }

    // protected function testWeeklyWithDayOverlap(): void
    // {
    //     $this->startDate = new DateTime('2024-09-16 01:00:00');
    //     $this->endDate = new DateTime('2024-09-17 03:00:00');

    //     $this->frequency = new Weekly([
    //         'repeatCount' => 1,
    //         'repeatDays' => ['monday', 'tuesday'],
    //         'repeatEnd' => new FrequencyRepeatEnd([
    //             'type' => 'after',
    //             'count' => 4,
    //         ]),
    //     ]);

    //     $this->expected = [
    //         ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
    //         ['startDate' => new DateTime('2024-09-23 01:00:00'), 'endDate' => new DateTime('2024-09-23 03:00:00')],
    //         ['startDate' => new DateTime('2024-09-24 01:00:00'), 'endDate' => new DateTime('2024-09-24 03:00:00')],
    //     ];
    // }

    protected function testWeeklyWithMultipleSequentialDays(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Weekly([
            'repeatCount' => 1,
            'repeatDays' => ['monday', 'tuesday'],
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 4,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
            ['startDate' => new DateTime('2024-09-23 01:00:00'), 'endDate' => new DateTime('2024-09-23 03:00:00')],
            ['startDate' => new DateTime('2024-09-24 01:00:00'), 'endDate' => new DateTime('2024-09-24 03:00:00')],
        ];
    }

    protected function testWeeklyWithMultipleSequentialDaysUnevenEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Weekly([
            'repeatCount' => 1,
            'repeatDays' => ['monday', 'tuesday'],
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 5,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
            ['startDate' => new DateTime('2024-09-23 01:00:00'), 'endDate' => new DateTime('2024-09-23 03:00:00')],
            ['startDate' => new DateTime('2024-09-24 01:00:00'), 'endDate' => new DateTime('2024-09-24 03:00:00')],
            ['startDate' => new DateTime('2024-09-30 01:00:00'), 'endDate' => new DateTime('2024-09-30 03:00:00')],
        ];
    }

    protected function testWeeklyWithMultipleNonSequentialDays(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Weekly([
            'repeatCount' => 1,
            'repeatDays' => ['monday', 'thursday', 'saturday'],
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 7,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-19 01:00:00'), 'endDate' => new DateTime('2024-09-19 03:00:00')],
            ['startDate' => new DateTime('2024-09-21 01:00:00'), 'endDate' => new DateTime('2024-09-21 03:00:00')],
            ['startDate' => new DateTime('2024-09-23 01:00:00'), 'endDate' => new DateTime('2024-09-23 03:00:00')],
            ['startDate' => new DateTime('2024-09-26 01:00:00'), 'endDate' => new DateTime('2024-09-26 03:00:00')],
            ['startDate' => new DateTime('2024-09-28 01:00:00'), 'endDate' => new DateTime('2024-09-28 03:00:00')],
            ['startDate' => new DateTime('2024-09-30 01:00:00'), 'endDate' => new DateTime('2024-09-30 03:00:00')],
        ];
    }

    protected function testWeeklyWithDateEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Weekly([
            'repeatCount' => 1,
            'repeatDays' => ['monday', 'tuesday', 'wednesday'],
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-09-27'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
            ['startDate' => new DateTime('2024-09-18 01:00:00'), 'endDate' => new DateTime('2024-09-18 03:00:00')],
            ['startDate' => new DateTime('2024-09-23 01:00:00'), 'endDate' => new DateTime('2024-09-23 03:00:00')],
            ['startDate' => new DateTime('2024-09-24 01:00:00'), 'endDate' => new DateTime('2024-09-24 03:00:00')],
            ['startDate' => new DateTime('2024-09-25 01:00:00'), 'endDate' => new DateTime('2024-09-25 03:00:00')],
        ];
    }

    protected function testWeeklyWithNumericEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Weekly([
            'repeatCount' => 1,
            'repeatDays' => ['monday', 'tuesday', 'wednesday'],
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 5,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
            ['startDate' => new DateTime('2024-09-18 01:00:00'), 'endDate' => new DateTime('2024-09-18 03:00:00')],
            ['startDate' => new DateTime('2024-09-23 01:00:00'), 'endDate' => new DateTime('2024-09-23 03:00:00')],
            ['startDate' => new DateTime('2024-09-24 01:00:00'), 'endDate' => new DateTime('2024-09-24 03:00:00')],
        ];
    }

    protected function testWeeklyWithDateEndOnMonday(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Weekly([
            'repeatCount' => 1,
            'repeatDays' => ['monday', 'tuesday', 'wednesday'],
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-09-30'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
            ['startDate' => new DateTime('2024-09-18 01:00:00'), 'endDate' => new DateTime('2024-09-18 03:00:00')],
            ['startDate' => new DateTime('2024-09-23 01:00:00'), 'endDate' => new DateTime('2024-09-23 03:00:00')],
            ['startDate' => new DateTime('2024-09-24 01:00:00'), 'endDate' => new DateTime('2024-09-24 03:00:00')],
            ['startDate' => new DateTime('2024-09-25 01:00:00'), 'endDate' => new DateTime('2024-09-25 03:00:00')],
            ['startDate' => new DateTime('2024-09-30 01:00:00'), 'endDate' => new DateTime('2024-09-30 03:00:00')],
        ];
    }

    protected function testWeeklyWithDateEndOnFriday(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Weekly([
            'repeatCount' => 1,
            'repeatDays' => ['monday', 'tuesday', 'wednesday'],
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-09-27'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
            ['startDate' => new DateTime('2024-09-18 01:00:00'), 'endDate' => new DateTime('2024-09-18 03:00:00')],
            ['startDate' => new DateTime('2024-09-23 01:00:00'), 'endDate' => new DateTime('2024-09-23 03:00:00')],
            ['startDate' => new DateTime('2024-09-24 01:00:00'), 'endDate' => new DateTime('2024-09-24 03:00:00')],
            ['startDate' => new DateTime('2024-09-25 01:00:00'), 'endDate' => new DateTime('2024-09-25 03:00:00')],
        ];
    }

    protected function testWeeklyRepeatTwoWithDateEndBeforeNextSet(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Weekly([
            'repeatCount' => 2,
            'repeatDays' => ['monday', 'tuesday', 'wednesday'],
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-09-27'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
            ['startDate' => new DateTime('2024-09-18 01:00:00'), 'endDate' => new DateTime('2024-09-18 03:00:00')],
        ];
    }

    protected function testWeeklyRepeatTwoWithDateEndOnNextSet(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Weekly([
            'repeatCount' => 2,
            'repeatDays' => ['monday', 'tuesday', 'wednesday'],
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-09-30'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
            ['startDate' => new DateTime('2024-09-18 01:00:00'), 'endDate' => new DateTime('2024-09-18 03:00:00')],
            ['startDate' => new DateTime('2024-09-30 01:00:00'), 'endDate' => new DateTime('2024-09-30 03:00:00')],
        ];
    }

    protected function testWeeklyRepeatTwoWithDateEndAfterNextSet(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Weekly([
            'repeatCount' => 2,
            'repeatDays' => ['monday', 'tuesday', 'wednesday'],
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-10-03'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
            ['startDate' => new DateTime('2024-09-18 01:00:00'), 'endDate' => new DateTime('2024-09-18 03:00:00')],
            ['startDate' => new DateTime('2024-09-30 01:00:00'), 'endDate' => new DateTime('2024-09-30 03:00:00')],
            ['startDate' => new DateTime('2024-10-01 01:00:00'), 'endDate' => new DateTime('2024-10-01 03:00:00')],
            ['startDate' => new DateTime('2024-10-02 01:00:00'), 'endDate' => new DateTime('2024-10-02 03:00:00')],
        ];
    }



    protected function testMonthly(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 1,
            'repeatDay' => 'onDate',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 4,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-10-16 01:00:00'), 'endDate' => new DateTime('2024-10-16 03:00:00')],
            ['startDate' => new DateTime('2024-11-16 01:00:00'), 'endDate' => new DateTime('2024-11-16 03:00:00')],
            ['startDate' => new DateTime('2024-12-16 01:00:00'), 'endDate' => new DateTime('2024-12-16 03:00:00')],
        ];
    }

    protected function testMonthlyWithDifferentEndDate(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-18 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 1,
            'repeatDay' => 'onDate',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 4,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-10-16 01:00:00'), 'endDate' => new DateTime('2024-10-18 03:00:00')],
            ['startDate' => new DateTime('2024-11-16 01:00:00'), 'endDate' => new DateTime('2024-11-18 03:00:00')],
            ['startDate' => new DateTime('2024-12-16 01:00:00'), 'endDate' => new DateTime('2024-12-18 03:00:00')],
        ];
    }

    // protected function testMonthlyWithDayOverlap(): void
    // {
    //     $this->startDate = new DateTime('2024-09-16 01:00:00');
    //     $this->endDate = new DateTime('2024-10-17 03:00:00');

    //     $this->frequency = new Monthly([
    //         'repeatCount' => 1,
            // 'repeatDay' => 'onDate',
    //         'repeatEnd' => new FrequencyRepeatEnd([
    //             'type' => 'after',
    //             'count' => 4,
    //         ]),
    //     ]);

    //     $this->expected = [
    //         ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
    //         ['startDate' => new DateTime('2024-09-23 01:00:00'), 'endDate' => new DateTime('2024-09-23 03:00:00')],
    //         ['startDate' => new DateTime('2024-09-24 01:00:00'), 'endDate' => new DateTime('2024-09-24 03:00:00')],
    //     ];
    // }

    protected function testMonthlyWithDateEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 1,
            'repeatDay' => 'onDate',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-11-20'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-10-16 01:00:00'), 'endDate' => new DateTime('2024-10-16 03:00:00')],
            ['startDate' => new DateTime('2024-11-16 01:00:00'), 'endDate' => new DateTime('2024-11-16 03:00:00')],
        ];
    }

    protected function testMonthlyWithNumericEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 1,
            'repeatDay' => 'onDate',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 5,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-10-16 01:00:00'), 'endDate' => new DateTime('2024-10-16 03:00:00')],
            ['startDate' => new DateTime('2024-11-16 01:00:00'), 'endDate' => new DateTime('2024-11-16 03:00:00')],
            ['startDate' => new DateTime('2024-12-16 01:00:00'), 'endDate' => new DateTime('2024-12-16 03:00:00')],
            ['startDate' => new DateTime('2025-01-16 01:00:00'), 'endDate' => new DateTime('2025-01-16 03:00:00')],
        ];
    }

    protected function testMonthlyRepeatTwoWithDateEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 2,
            'repeatDay' => 'onDate',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-11-20'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-11-16 01:00:00'), 'endDate' => new DateTime('2024-11-16 03:00:00')],
        ];
    }

    protected function testMonthlyRepeatTwoWithNumericEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 2,
            'repeatDay' => 'onDate',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 5,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-11-16 01:00:00'), 'endDate' => new DateTime('2024-11-16 03:00:00')],
            ['startDate' => new DateTime('2025-01-16 01:00:00'), 'endDate' => new DateTime('2025-01-16 03:00:00')],
            ['startDate' => new DateTime('2025-03-16 01:00:00'), 'endDate' => new DateTime('2025-03-16 03:00:00')],
            ['startDate' => new DateTime('2025-05-16 01:00:00'), 'endDate' => new DateTime('2025-05-16 03:00:00')],
        ];
    }

    protected function testMonthlyRepeatThreeWithDateEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 3,
            'repeatDay' => 'onDate',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-12-20'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-12-16 01:00:00'), 'endDate' => new DateTime('2024-12-16 03:00:00')],
        ];
    }

    protected function testMonthlyRepeatThreeWithNumericEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 3,
            'repeatDay' => 'onDate',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 5,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-12-16 01:00:00'), 'endDate' => new DateTime('2024-12-16 03:00:00')],
            ['startDate' => new DateTime('2025-03-16 01:00:00'), 'endDate' => new DateTime('2025-03-16 03:00:00')],
            ['startDate' => new DateTime('2025-06-16 01:00:00'), 'endDate' => new DateTime('2025-06-16 03:00:00')],
            ['startDate' => new DateTime('2025-09-16 01:00:00'), 'endDate' => new DateTime('2025-09-16 03:00:00')],
        ];
    }

    protected function testMonthlyRepeatWithDateEndSameDate(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 1,
            'repeatDay' => 'onDate',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-10-16'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-10-16 01:00:00'), 'endDate' => new DateTime('2024-10-16 03:00:00')],
        ];
    }

    protected function testMonthlyRepeatWithDateEndBeforeDate(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 1,
            'repeatDay' => 'onDate',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-10-01'),
            ]),
        ]);

        $this->expected = [];
    }

    protected function testMonthlyOnDay(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 1,
            'repeatDay' => 'onDay',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 4,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-10-21 01:00:00'), 'endDate' => new DateTime('2024-10-21 03:00:00')],
            ['startDate' => new DateTime('2024-11-18 01:00:00'), 'endDate' => new DateTime('2024-11-18 03:00:00')],
            ['startDate' => new DateTime('2024-12-16 01:00:00'), 'endDate' => new DateTime('2024-12-16 03:00:00')],
        ];
    }

    protected function testMonthlyOnDayWithDifferentEndDate(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-18 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 1,
            'repeatDay' => 'onDay',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 4,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-10-21 01:00:00'), 'endDate' => new DateTime('2024-10-23 03:00:00')],
            ['startDate' => new DateTime('2024-11-18 01:00:00'), 'endDate' => new DateTime('2024-11-20 03:00:00')],
            ['startDate' => new DateTime('2024-12-16 01:00:00'), 'endDate' => new DateTime('2024-12-18 03:00:00')],
        ];
    }

    // protected function testMonthlyOnDayWithDayOverlap(): void
    // {
    //     $this->startDate = new DateTime('2024-09-16 01:00:00');
    //     $this->endDate = new DateTime('2024-10-17 03:00:00');

    //     $this->frequency = new Monthly([
    //         'repeatCount' => 1,
            // 'repeatDay' => 'onDay',
    //         'repeatEnd' => new FrequencyRepeatEnd([
    //             'type' => 'after',
    //             'count' => 4,
    //         ]),
    //     ]);

    //     $this->expected = [
    //         ['startDate' => new DateTime('2024-09-17 01:00:00'), 'endDate' => new DateTime('2024-09-17 03:00:00')],
    //         ['startDate' => new DateTime('2024-09-23 01:00:00'), 'endDate' => new DateTime('2024-09-23 03:00:00')],
    //         ['startDate' => new DateTime('2024-09-24 01:00:00'), 'endDate' => new DateTime('2024-09-24 03:00:00')],
    //     ];
    // }

    protected function testMonthlyOnDayWithDateEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 1,
            'repeatDay' => 'onDay',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-11-20'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-10-21 01:00:00'), 'endDate' => new DateTime('2024-10-21 03:00:00')],
            ['startDate' => new DateTime('2024-11-18 01:00:00'), 'endDate' => new DateTime('2024-11-18 03:00:00')],
        ];
    }

    protected function testMonthlyOnDayWithNumericEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 1,
            'repeatDay' => 'onDay',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 5,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-10-21 01:00:00'), 'endDate' => new DateTime('2024-10-21 03:00:00')],
            ['startDate' => new DateTime('2024-11-18 01:00:00'), 'endDate' => new DateTime('2024-11-18 03:00:00')],
            ['startDate' => new DateTime('2024-12-16 01:00:00'), 'endDate' => new DateTime('2024-12-16 03:00:00')],
            ['startDate' => new DateTime('2025-01-20 01:00:00'), 'endDate' => new DateTime('2025-01-20 03:00:00')],
        ];
    }

    protected function testMonthlyOnDayRepeatTwoWithDateEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 2,
            'repeatDay' => 'onDay',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-11-20'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-11-18 01:00:00'), 'endDate' => new DateTime('2024-11-18 03:00:00')],
        ];
    }

    protected function testMonthlyOnDayRepeatTwoWithNumericEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 2,
            'repeatDay' => 'onDay',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 5,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-11-18 01:00:00'), 'endDate' => new DateTime('2024-11-18 03:00:00')],
            ['startDate' => new DateTime('2025-01-20 01:00:00'), 'endDate' => new DateTime('2025-01-20 03:00:00')],
            ['startDate' => new DateTime('2025-03-17 01:00:00'), 'endDate' => new DateTime('2025-03-17 03:00:00')],
            ['startDate' => new DateTime('2025-05-19 01:00:00'), 'endDate' => new DateTime('2025-05-19 03:00:00')],
        ];
    }

    protected function testMonthlyOnDayRepeatThreeWithDateEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 3,
            'repeatDay' => 'onDay',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-12-20'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-12-16 01:00:00'), 'endDate' => new DateTime('2024-12-16 03:00:00')],
        ];
    }

    protected function testMonthlyOnDayRepeatThreeWithNumericEnd(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 3,
            'repeatDay' => 'onDay',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'after',
                'count' => 5,
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-12-16 01:00:00'), 'endDate' => new DateTime('2024-12-16 03:00:00')],
            ['startDate' => new DateTime('2025-03-17 01:00:00'), 'endDate' => new DateTime('2025-03-17 03:00:00')],
            ['startDate' => new DateTime('2025-06-16 01:00:00'), 'endDate' => new DateTime('2025-06-16 03:00:00')],
            ['startDate' => new DateTime('2025-09-15 01:00:00'), 'endDate' => new DateTime('2025-09-15 03:00:00')],
        ];
    }

    protected function testMonthlyOnDayRepeatWithDateEndSameDate(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 1,
            'repeatDay' => 'onDay',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-10-21'),
            ]),
        ]);

        $this->expected = [
            ['startDate' => new DateTime('2024-10-21 01:00:00'), 'endDate' => new DateTime('2024-10-21 03:00:00')],
        ];
    }

    protected function testMonthlyOnDayRepeatWithDateEndBeforeDate(): void
    {
        $this->startDate = new DateTime('2024-09-16 01:00:00');
        $this->endDate = new DateTime('2024-09-16 03:00:00');

        $this->frequency = new Monthly([
            'repeatCount' => 1,
            'repeatDay' => 'onDay',
            'repeatEnd' => new FrequencyRepeatEnd([
                'type' => 'until',
                'date' => new DateTime('2024-10-01'),
            ]),
        ]);

        $this->expected = [];
    }




    // Private Methods
    // =========================================================================

    private function _recurringSessionDatesTest(string $methodName): void
    {
        $dates = $this->frequency->getRecurringSessionDates(new Session([
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
        ]));

        $result = $this->_areDateTimeArraysEqual($dates, $this->expected);

        $this->stdout('Running ' . implode(' ', StringHelper::toWords($methodName, true)) . ' ... ');

        if ($result) {
            $this->stdout('success' . PHP_EOL, Console::FG_GREEN);
        } else {
            $this->stdout('fail' . PHP_EOL, Console::FG_RED);
        }
    }

    private function _areDateTimeArraysEqual(array $array1, array $array2): bool
    {
        // Check if both arrays have the same number of elements
        if (count($array1) !== count($array2)) {
            return false;
        }

        // Loop through each element to compare the DateTime objects
        foreach ($array1 as $index => $datePair1) {
            // Check if the current elements are arrays with 'startDate' and 'endDate' keys
            if (!isset($datePair1['startDate'], $datePair1['endDate'], $array2[$index]['startDate'], $array2[$index]['endDate'])) {
                return false;
            }

            // Ensure both elements at this index are instances of DateTime
            if (!($datePair1['startDate'] instanceof DateTime) || !($datePair1['endDate'] instanceof DateTime) ||
                !($array2[$index]['startDate'] instanceof DateTime) || !($array2[$index]['endDate'] instanceof DateTime)) {
                return false;
            }

            // Compare the 'startDate' and 'endDate' DateTime objects using their timestamps
            if ($datePair1['startDate']->getTimestamp() !== $array2[$index]['startDate']->getTimestamp() ||
                $datePair1['endDate']->getTimestamp() !== $array2[$index]['endDate']->getTimestamp()) {
                return false;
            }
        }

        return true;
    }

}
