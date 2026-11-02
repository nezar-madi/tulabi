<?php

namespace Tests\Unit;

use App\Support\TulabiClock;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TulabiClockTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_storage_stays_utc_and_the_local_zone_is_tripoli(): void
    {
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('Africa/Tripoli', TulabiClock::timezone());
    }

    public function test_today_follows_tripoli_around_midnight(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-01 21:59:00', 'UTC'));
        $this->assertSame('2026-03-01', TulabiClock::today());

        Carbon::setTestNow(Carbon::parse('2026-03-01 22:00:00', 'UTC'));
        $this->assertSame('2026-03-02', TulabiClock::today());
    }

    public function test_a_typed_date_is_stored_as_local_midnight_in_utc(): void
    {
        $stored = TulabiClock::storeDate('2026-03-02');

        $this->assertSame('2026-03-01 22:00:00', $stored->format('Y-m-d H:i:s'));
        $this->assertSame('UTC', $stored->getTimezone()->getName());
        $this->assertSame('2026-03-02 21:59:59', TulabiClock::endOfDayUtc('2026-03-02')->format('Y-m-d H:i:s'));
    }
}
