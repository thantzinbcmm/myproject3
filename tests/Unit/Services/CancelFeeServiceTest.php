// tests/Unit/Services/CancelFeeServiceTest.php
<?php

namespace Tests\Unit\Services;

use App\Services\CancelFeeService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class CancelFeeServiceTest extends TestCase
{
    private CancelFeeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CancelFeeService();
    }

    public function test_free_cancel_5_days_before(): void
    {
        $checkIn = Carbon::today()->addDays(10);
        $cancelDate = Carbon::today()->addDays(5);
        $totalAmount = 30000;

        $result = $this->service->calculate($checkIn, $totalAmount, $cancelDate);

        $this->assertEquals(0, $result['rate']);
        $this->assertEquals(0, $result['fee']);
        $this->assertEquals('FREE', $result['policy']);
    }

    public function test_50_percent_cancel_day_before(): void
    {
        $checkIn = Carbon::today()->addDay();
        $cancelDate = Carbon::today();
        $totalAmount = 30000;

        $result = $this->service->calculate($checkIn, $totalAmount, $cancelDate);

        $this->assertEquals(50, $result['rate']);
        $this->assertEquals(15000, $result['fee']);
        $this->assertEquals('DAY_BEFORE', $result['policy']);
    }

    public function test_100_percent_cancel_same_day(): void
    {
        $checkIn = Carbon::today();
        $cancelDate = Carbon::today();
        $totalAmount = 30000;

        $result = $this->service->calculate($checkIn, $totalAmount, $cancelDate);

        $this->assertEquals(100, $result['rate']);
        $this->assertEquals(30000, $result['fee']);
        $this->assertEquals('SAME_DAY', $result['policy']);
    }

    public function test_100_percent_noshow(): void
    {
        $checkIn = Carbon::yesterday();
        $cancelDate = Carbon::today();
        $totalAmount = 50000;

        $result = $this->service->calculate($checkIn, $totalAmount, $cancelDate);

        $this->assertEquals(100, $result['rate']);
        $this->assertEquals(50000, $result['fee']);
        $this->assertEquals('NOSHOW', $result['policy']);
    }

    public function test_free_cancel_2_days_before(): void
    {
        $checkIn = Carbon::today()->addDays(3);
        $cancelDate = Carbon::today()->addDay();
        $totalAmount = 20000;

        $result = $this->service->calculate($checkIn, $totalAmount, $cancelDate);

        $this->assertEquals(0, $result['rate']);
        $this->assertEquals(0, $result['fee']);
    }

    public function test_free_cancel_until_date(): void
    {
        $checkIn = Carbon::create(2024, 1, 15);
        $freeCancelUntil = $this->service->getFreeCancelUntil($checkIn);

        $this->assertEquals('2024-01-13', $freeCancelUntil->format('Y-m-d'));
    }
}