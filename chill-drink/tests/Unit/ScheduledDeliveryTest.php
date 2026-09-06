<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Support\ScheduledDelivery;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ScheduledDeliveryTest extends TestCase
{
    public function test_delivery_schedule_reserves_payment_and_operation_time(): void
    {
        Carbon::setTestNow('2026-07-08 09:00:00');
        $this->assertNotNull(ScheduledDelivery::validate('2026-07-08 09:44:00', 'delivery'));
        $this->assertNull(ScheduledDelivery::validate('2026-07-08 09:45:00', 'delivery'));
        $this->assertNotNull(ScheduledDelivery::validate('2026-07-08 09:44:00', 'pickup'));
        $this->assertNull(ScheduledDelivery::validate('2026-07-08 09:45:00', 'pickup'));
        Carbon::setTestNow();
    }

    public function test_schedule_must_be_inside_opening_hours(): void
    {
        Carbon::setTestNow('2026-07-08 06:00:00');
        $this->assertNotNull(ScheduledDelivery::validate('2026-07-08 06:45:00', 'delivery'));
        $this->assertNull(ScheduledDelivery::validate('2026-07-08 07:00:00', 'delivery'));
        $this->assertNull(ScheduledDelivery::validate('2026-07-08 22:00:00', 'delivery'));
        $this->assertNotNull(ScheduledDelivery::validate('2026-07-08 22:01:00'));
        Carbon::setTestNow();
    }

    public function test_schedule_must_be_on_the_same_day(): void
    {
        Carbon::setTestNow('2026-07-08 09:00:00');
        $this->assertNull(ScheduledDelivery::validate('2026-07-08 09:45:00'));
        $this->assertSame(
            'Đặt giao sau chỉ áp dụng trong ngày hôm nay.',
            ScheduledDelivery::validate('2026-07-09 10:00:00')
        );
        Carbon::setTestNow();
    }

    public function test_scheduled_delivery_can_start_operation_in_the_last_thirty_minutes(): void
    {
        Carbon::setTestNow('2026-07-08 09:29:00');
        $order = new Order([
            'delivery_type' => 'scheduled',
            'scheduled_delivery_time' => '2026-07-08 10:00:00',
        ]);

        $this->assertFalse(ScheduledDelivery::canStartPreparation($order));

        Carbon::setTestNow('2026-07-08 09:30:00');
        $this->assertTrue(ScheduledDelivery::canStartPreparation($order));
        Carbon::setTestNow();
    }
}
