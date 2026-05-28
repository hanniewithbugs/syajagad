<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/password', [
            'old_password' => 'oldpassword',
            'new_password' => 'newpassword',
            'new_password_confirmation' => 'newpassword',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('newpassword', $admin->fresh()->password));
    }

    public function test_admin_cannot_change_password_with_wrong_old_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/password', [
            'old_password' => 'wrongpassword',
            'new_password' => 'newpassword',
            'new_password_confirmation' => 'newpassword',
        ]);

        $response->assertUnprocessable();
        $this->assertTrue(Hash::check('oldpassword', $admin->fresh()->password));
    }
}
