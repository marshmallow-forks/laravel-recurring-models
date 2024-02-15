<?php

use Illuminate\Support\Carbon;
use MohammedManssour\LaravelRecurringModels\Tests\Stubs\Support\HasTask;
use MohammedManssour\LaravelRecurringModels\Tests\TestCase;

class RepetitionTest extends TestCase
{
    use HasTask;

    /**
     * @test
     * */
    public function it_checks_if_recurring_is_active_for_the_date()
    {
        // repeat start at 2023-04-15 00:00:00 and ends at 2023-04-15
        $repetition = $this->repetition($this->task(), '2023-04-30');

        $this->assertNull(config('recurring-models.models.repetition')::query()->whereActiveForTheDate(Carbon::make('2023-04-14 00:00:00'))->first());

        $this->assertNull(config('recurring-models.models.repetition')::query()->whereActiveForTheDate(Carbon::make('2023-05-01 00:00:00'))->first());

        $activeTestDates = ['2023-04-15', '2023-04-16', '2023-04-30'];
        foreach ($activeTestDates as $date) {
            $model = config('recurring-models.models.repetition')::query()->whereActiveForTheDate(Carbon::make("{$date} 00:00:00"))->first();
            $this->assertTrue($model->is($repetition));
        }
    }

    /** @test */
    public function it_checks_if_simple_repetition_occurres_on_specific_day()
    {
        // repeat start at 2023-04-15 00:00:00
        $repetition = $this->repetition($this->task());

        $model = config('recurring-models.models.repetition')::whereOccurresOn(Carbon::make('2023-04-10 00:00:00'))->first();
        $this->assertNull($model);

        $model = config('recurring-models.models.repetition')::whereOccurresOn(Carbon::make('2023-04-20 23:00:00'))->first();
        $this->assertTrue($repetition->is($model));

        $model = config('recurring-models.models.repetition')::whereOccurresOn(Carbon::make('2023-04-16 23:00:00'))->first();
        $this->assertFalse($repetition->is($model));

        $model = config('recurring-models.models.repetition')::whereOccurresOn(Carbon::make('2023-04-17 23:00:00'))->first();
        $this->assertFalse($repetition->is($model));

        $model = config('recurring-models.models.repetition')::whereOccurresOn(Carbon::make('2023-04-18 23:00:00'))->first();
        $this->assertFalse($repetition->is($model));

        $model = config('recurring-models.models.repetition')::whereOccurresOn(Carbon::make('2023-04-19 23:00:00'))->first();
        $this->assertFalse($repetition->is($model));

        // ensure the day no matter what the hour is. usefull when handling timezones
        $model = config('recurring-models.models.repetition')::whereOccurresOn(Carbon::make('2023-04-20 23:00:00'))->first();
        $this->assertTrue($repetition->is($model));

        $model = config('recurring-models.models.repetition')::whereOccurresOn(Carbon::make('2023-04-25 00:00:00'))->first();
        $this->assertTrue($repetition->is($model));

        $date = Carbon::make('2023-04-25 00:00:00');
        $model = config('recurring-models.models.repetition')::whereHasSimpleRecurringOn($date)->first();
        $this->assertTrue($repetition->is($model));

        $this->assertNull(config('recurring-models.models.repetition')::whereHasComplexRecurringOn($date)->first());
    }

    /** @test */
    public function it_checks_if_simple_repetition_occurres_on_specific_day_with_timezone()
    {
        Carbon::setTestNowAndTimezone(
            Carbon::make(self::NOW)->subHours(4)->toDateTimeString(),
            'Asia/Dubai'
        );
        // repetition starts at 2023-04-21 00:00:00 (Asia/Dubai) = 2023-04-20 20:00:00 UTC
        $this->task()->repeat()->everyNDays(2);
        $repetition = $this->task()->repetitions->first();

        $model = config('recurring-models.models.repetition')::whereOccurresOn(now()->addDays(2))->first();
        $this->assertTrue($model->is($repetition));

        // using different timezone will yield to the same repetition model because repetition data is saved/calculated in utc
        $model = config('recurring-models.models.repetition')::whereOccurresOn(now()->addDays(2)->setTimezone('Asia/Riyadh'))->first();
        $this->assertTrue($model->is($repetition));

        $model = config('recurring-models.models.repetition')::whereOccurresOn(now()->addDays(3)->setTimezone('Asia/Riyadh'))->first();
        $this->assertNull($model);
    }

    /** @test */
    public function it_checks_if_simple_repetition_occurres_on_specific_dates_after_end_date()
    {
        $this->repetition($this->task(), '2023-04-23 00:00:00');

        $model = config('recurring-models.models.repetition')::whereOccurresOn(Carbon::make('2023-04-25 00:00:00'))->first();
        $this->assertNull($model);
    }

    /** @test */
    public function it_checks_if_simple_repetition_occurres_between_two_specific_dates()
    {
        // repeat start at 2023-04-15 00:00:00
        $repetition = $this->repetition($this->task());

        $model = config('recurring-models.models.repetition')::whereOccurresBetween(
            Carbon::make('2023-04-20 00:00:00'),
            Carbon::make('2023-04-25 00:00:00'),
        )->first();
        $this->assertTrue($repetition->is($model));

        $this->assertFalse(
            config('recurring-models.models.repetition')::whereOccurresBetween(
                Carbon::make('2023-04-10 00:00:00'),
                Carbon::make('2023-04-14 00:00:00'),
            )
                ->exists()
        );
    }

    /** @test */
    public function it_checks_if_complex_repetition_occurres_on_specific_dates()
    {
        Carbon::setTestNow(
            Carbon::make('2023-04-20')
        );

        // repeats on second Friday of the month
        $repetition = config('recurring-models.models.repetition')::factory()
            ->morphs($this->task())
            ->complex(weekOfMonth: 2, weekday: Carbon::FRIDAY)
            ->starts($this->task()->repetitionBaseDate())
            ->create();

        $date = new Carbon('2023-05-12 00:00:00');
        $model = config('recurring-models.models.repetition')::whereOccurresOn($date)->first();
        $this->assertTrue($model->is($repetition));

        $date = Carbon::make('2023-06-09');
        $model = config('recurring-models.models.repetition')::whereOccurresOn($date)->first();
        $this->assertTrue($model->is($repetition));

        $model = config('recurring-models.models.repetition')::whereHasComplexRecurringOn($date)->first();
        $this->assertTrue($model->is($repetition));

        $this->assertNull(config('recurring-models.models.repetition')::whereHasSimpleRecurringOn($date)->first());
        $this->assertNull(config('recurring-models.models.repetition')::whereOccurresOn(Carbon::make('2023-05-05'))->first());
        $this->assertNull(config('recurring-models.models.repetition')::whereOccurresOn(Carbon::make('2023-05-19'))->first());
    }

    /** @test */
    public function it_checks_if_complex_repetition_occurres_on_specific_dates_with_timezone()
    {
        Carbon::setTestNowAndTimezone(
            Carbon::make(self::NOW)->subHours(4)->toDateTimeString(),
            'Asia/Dubai'
        );

        // repeats on second Friday of the month
        $repetition = config('recurring-models.models.repetition')::factory()
            ->morphs($this->task())
            ->complex(weekOfMonth: 2, weekday: Carbon::FRIDAY)
            ->starts($this->task()->repetitionBaseDate())
            ->create([
                'tz_offset' => 4 * 3600,
            ]);

        $date = new Carbon('2023-05-12 00:00:00');
        $model = config('recurring-models.models.repetition')::whereOccurresOn($date)->first();
        $this->assertTrue($model->is($repetition));

        $date = Carbon::make('2023-06-09');
        $model = config('recurring-models.models.repetition')::whereOccurresOn($date)->first();
        $this->assertTrue($model->is($repetition));

        $model = config('recurring-models.models.repetition')::whereHasComplexRecurringOn($date)->first();
        $this->assertTrue($model->is($repetition));

        $this->assertNull(config('recurring-models.models.repetition')::whereHasSimpleRecurringOn($date)->first());
        $this->assertNull(config('recurring-models.models.repetition')::whereOccurresOn(Carbon::make('2023-05-05'))->first());
        $this->assertNull(config('recurring-models.models.repetition')::whereOccurresOn(Carbon::make('2023-05-19'))->first());
    }
}
