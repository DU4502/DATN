<?php

namespace Tests\Unit;

use App\Support\ScheduledDelivery;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScheduledDeliveryTest extends TestCase
{
    public function test_schedule_requires_thirty_minutes_preparation(): void
    {
        Carbon::setTestNow('2026-07-08 09:00:00');
        $this->assertNotNull(ScheduledDelivery::validate('2026-07-08 09:29:00'));
        $this->assertNull(ScheduledDelivery::validate('2026-07-08 09:30:00'));
        Carbon::setTestNow();
    }

    public function test_schedule_must_be_inside_opening_hours(): void
    {
        Carbon::setTestNow('2026-07-08 06:00:00');
        $this->assertNotNull(ScheduledDelivery::validate('2026-07-08 06:45:00'));
        $this->assertNull(ScheduledDelivery::validate('2026-07-08 07:00:00'));
        $this->assertNull(ScheduledDelivery::validate('2026-07-08 22:00:00'));
        $this->assertNotNull(ScheduledDelivery::validate('2026-07-08 22:01:00'));
        Carbon::setTestNow();
    }
}
