<?php

namespace Tests\Feature;

use App\Services\ContentPlanService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContentPlanScheduleTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_selected_weekdays_fill_a_one_month_horizon(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-08 15:00:00', 'Europe/Zurich'));

        $slots = app(ContentPlanService::class)->buildSlots(
            1,
            3,
            ['friday', 'monday', 'wednesday'],
            '09:00',
            'Europe/Zurich',
        );

        $local = array_map(
            fn (Carbon $slot) => $slot->copy()->timezone('Europe/Zurich')->format('Y-m-d H:i'),
            $slots,
        );

        $this->assertSame('2026-10-09 09:00', $local[0]);
        $this->assertSame('2026-11-06 09:00', $local[array_key_last($local)]);
        $this->assertCount(13, $local);
        $this->assertNotContains('2026-10-08 09:00', $local);
    }

    public function test_weekly_frequency_uses_a_stable_day_pattern(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-08 15:00:00', 'Europe/Zurich'));

        $slots = app(ContentPlanService::class)->buildSlots(1, 2, [], '09:00', 'Europe/Zurich');
        $local = array_map(
            fn (Carbon $slot) => $slot->copy()->timezone('Europe/Zurich')->format('Y-m-d D H:i'),
            $slots,
        );

        $this->assertSame('2026-10-13 Tue 09:00', $local[0]);
        $this->assertSame('2026-10-15 Thu 09:00', $local[1]);
        $this->assertCount(8, $local);
    }

    public function test_long_plans_stop_at_the_configured_post_cap(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-08 15:00:00', 'Europe/Zurich'));

        $slots = app(ContentPlanService::class)->buildSlots(
            6,
            7,
            ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            '09:00',
            'Europe/Zurich',
        );

        $this->assertCount(72, $slots);
    }

    public function test_day_post_types_follow_selected_weekdays(): void
    {
        $types = app(ContentPlanService::class)->resolvedDayPostTypes(
            ['friday', 'monday', 'thursday'],
            [
                'thursday' => 'promotional',
                'monday' => 'Educational',
                'friday' => 'engagement',
                'sunday' => 'announcement',
            ],
        );

        $this->assertSame([
            'monday' => 'educational',
            'thursday' => 'promotional',
            'friday' => 'engagement',
        ], $types);
    }

    public function test_weekdays_are_not_required_for_a_frequency_plan(): void
    {
        $this->assertSame([], app(ContentPlanService::class)->resolvedDayPostTypes([], [
            'monday' => 'educational',
        ]));
    }

    public function test_a_selected_day_without_a_post_type_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(ContentPlanService::class)->resolvedDayPostTypes(
            ['monday'],
            ['monday' => ''],
        );
    }

    public function test_each_slot_keeps_the_post_type_of_its_local_weekday(): void
    {
        $service = app(ContentPlanService::class);
        $types = [
            'monday' => 'educational',
            'thursday' => 'promotional',
        ];

        $monday = Carbon::parse('2026-10-12 09:00:00', 'Europe/Zurich');
        $thursday = Carbon::parse('2026-10-15 09:00:00', 'Europe/Zurich');

        $this->assertSame('educational', $service->postTypeForMoment($monday, 'Europe/Zurich', $types));
        $this->assertSame('promotional', $service->postTypeForMoment($thursday, 'Europe/Zurich', $types));
        $this->assertNull($service->postTypeForMoment($thursday, 'Europe/Zurich', []));
    }
}
