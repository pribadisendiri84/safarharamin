<?php

namespace Tests\Unit;

use App\Models\Setting;
use App\Support\PriceSyncSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceSyncScheduleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_it_detects_daily_schedule_as_due(): void
    {
        PriceSyncSchedule::save([
            'enabled' => true,
            'frequency' => PriceSyncSchedule::FREQUENCY_DAILY,
            'time' => '02:00',
            'days' => [1],
            'update_mode' => PriceSyncSchedule::MODE_MANUAL,
            'timezone' => 'Asia/Jakarta',
        ]);

        $now = Carbon::parse('2026-09-11 03:00:00', 'Asia/Jakarta');
        $this->assertTrue(PriceSyncSchedule::isDue($now));
    }

    #[Test]
    public function test_it_is_not_due_when_disabled(): void
    {
        PriceSyncSchedule::save([
            'enabled' => false,
            'frequency' => PriceSyncSchedule::FREQUENCY_DAILY,
            'time' => '02:00',
            'days' => [1],
            'update_mode' => PriceSyncSchedule::MODE_MANUAL,
            'timezone' => 'Asia/Jakarta',
        ]);

        $this->assertFalse(PriceSyncSchedule::isDue(Carbon::parse('2026-09-11 03:00:00', 'Asia/Jakarta')));
    }

    #[Test]
    public function test_mark_ran_persists_last_run_timestamp(): void
    {
        PriceSyncSchedule::markRan();

        $this->assertNotSame('', Setting::getValue(PriceSyncSchedule::KEY_LAST_RUN_AT));
    }
}
