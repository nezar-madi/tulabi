<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_renders_in_arabic_right_to_left(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewIs('home')
            ->assertSee('<html lang="ar" dir="rtl">', false)
            ->assertSee('انضم كمدرّس')
            ->assertSee(route('join'), false)
            ->assertSee(route('login'), false);
    }

    public function test_a_signed_in_user_sees_their_account_link(): void
    {
        $instructor = User::factory()->instructor()->create();

        $this->actingAs($instructor)->get('/')
            ->assertOk()
            ->assertSee(route('teach.dashboard'), false)
            ->assertSee('حسابي');
    }
}
