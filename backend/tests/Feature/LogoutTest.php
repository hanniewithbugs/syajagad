<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_logout(): void
    {
        $user = User::factory()->create([
            'nis' => 'A001',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_dashboard_contains_logout_form(): void
    {
        $user = User::factory()->create([
            'nis' => 'A002',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($user)->get('/dbAdmin');

        $response->assertOk();
        $response->assertSee('id="logoutForm"', false);
        $response->assertSee('action="'.route('logout').'"', false);
    }
}
