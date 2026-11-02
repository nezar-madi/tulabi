<?php

namespace Tests\Feature;

use App\Support\Currency;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_home_page_renders_in_arabic_right_to_left(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('currency', Currency::CODE)
                ->has('today'));
    }

    public function test_today_is_the_date_in_tripoli_not_utc(): void
    {
        // 23:30 UTC on 1 March is already 01:30 on 2 March in Tripoli (UTC+2).
        Carbon::setTestNow(Carbon::parse('2026-03-01 23:30:00', 'UTC'));

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('today', '2026-03-02'));
    }
}
