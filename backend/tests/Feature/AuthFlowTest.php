<?php

namespace Tests\Feature;

use App\Models\Invoice;
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

    public function test_santri_can_login_with_username(): void
    {
        $user = User::factory()->create([
            'nis' => 'S011',
            'username' => 'santriuser',
            'role' => 'santri',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'santriuser',
            'password' => 'secret123',
            'role' => 'santri',
        ]);

        $response->assertRedirect(route('dbSantri'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_santri_can_login_with_formatted_or_compact_nis(): void
    {
        $user = User::factory()->create([
            'nis' => '24 008 847',
            'role' => 'santri',
            'password' => Hash::make('Jagad847'),
        ]);

        $response = $this->post('/login', [
            'email' => '24008847',
            'password' => 'Jagad847',
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

    public function test_authenticated_user_is_redirected_from_welcome_to_dashboard(): void
    {
        $user = User::factory()->create([
            'nis' => 'S005',
            'role' => 'santri',
        ]);

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dbSantri'));
    }

    public function test_dashboard_route_redirects_by_role(): void
    {
        $admin = User::factory()->create([
            'nis' => 'A005',
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertRedirect(route('dbAdmin'));
    }

    public function test_santri_dashboard_loads_with_paid_invoice_date(): void
    {
        $user = User::factory()->create([
            'nis' => 'S006',
            'role' => 'santri',
        ]);

        Invoice::create([
            'user_id' => $user->id,
            'name' => 'SPP Semester Genap 2026',
            'description' => 'Pembayaran SPP semester genap 2026',
            'due_date' => '2026-01-15',
            'amount' => 2200000,
            'penalty' => 0,
            'total' => 2200000,
            'status' => 'lunas',
            'payment_method' => 'qris',
            'paid_date' => '2026-02-01',
        ]);

        $response = $this->actingAs($user)->get('/dbSantri');

        $response->assertOk();
    }

    public function test_admin_can_login_with_email(): void
    {
        $user = User::factory()->create([
            'nis' => 'A001',
            'role' => 'admin',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'secret123',
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('dbAdmin'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_public_register_cannot_create_admin(): void
    {
        $response = $this->post('/register', [
            'nis' => 'A002',
            'name' => 'Admin User',
            'tgl_lahir' => '1990-05-11',
            'alamat' => 'Alamat Admin',
            'role' => 'admin',
            'email' => 'admin@example.com',
            'username' => 'adminuser',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'admin@example.com',
        ]);
    }

    public function test_register_santri_redirects_to_dbSantri(): void
    {
        $response = $this->post('/register', [
            'nis' => 'S004',
            'name' => 'Santri User',
            'tgl_lahir' => '2005-03-15',
            'alamat' => 'Alamat Santri',
            'role' => 'santri',
            'email' => 'santri@example.com',
            'username' => 'santriuser',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect(route('dbSantri'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'santri@example.com',
            'role' => 'santri',
        ]);
    }

    public function test_santri_can_update_profile(): void
    {
        $user = User::factory()->create([
            'nis' => 'S009',
            'role' => 'santri',
            'email' => 'old@example.com',
            'username' => 'olduser',
            'alamat' => 'Alamat Lama',
        ]);

        $response = $this->actingAs($user)->putJson('/santri/profile', [
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
            'username' => 'baruuser',
            'alamat' => 'Alamat Baru',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.name', 'Nama Baru');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'baru@example.com',
            'username' => 'baruuser',
            'alamat' => 'Alamat Baru',
        ]);
    }

    public function test_santri_can_change_password(): void
    {
        $user = User::factory()->create([
            'nis' => 'S010',
            'role' => 'santri',
            'password' => Hash::make('oldpass123'),
        ]);

        $response = $this->actingAs($user)->postJson('/santri/password', [
            'old_password' => 'oldpass123',
            'new_password' => 'newpass123',
            'new_password_confirmation' => 'newpass123',
        ]);

        $response->assertOk();
        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
    }

    public function test_public_pages_are_accessible(): void
    {
        $this->get('/')->assertOk();
        $this->get('/login')->assertOk()->assertSee('Masuk ke Sistem');
        $this->get('/register')->assertOk()->assertSee('Buat Akun Baru');
        $this->get('/')->assertSee('Daftar Santri');
    }
}
