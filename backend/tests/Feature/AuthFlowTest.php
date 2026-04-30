<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_santri_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'nis' => 'S001',
            'role' => 'santri',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
            'role' => 'santri',
        ]);

        $response->assertRedirect(route('dbSantri'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_santri_can_login_with_nis(): void
    {
        $user = User::factory()->create([
            'nis' => 'S002',
            'role' => 'santri',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'S002',
            'password' => 'secret123',
            'role' => 'santri',
        ]);

        $response->assertRedirect(route('dbSantri'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_role_cannot_access_other_dashboard(): void
    {
        $user = User::factory()->create([
            'nis' => 'S003',
            'role' => 'santri',
        ]);

        $response = $this->actingAs($user)->get('/dbAdmin');

        $response->assertForbidden();
    }
}
