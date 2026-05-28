<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSecurityAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_action_is_blocked_without_permission(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_permissions' => ['view_audit_logs'],
        ]);
        $santri = User::factory()->create(['role' => 'santri', 'nis' => 'SPERM001']);

        $response = $this->actingAs($admin)->postJson("/admin/santri/{$santri->id}/invoices", [
            'name' => 'SPP Semester Ganjil 2026',
            'due_date' => '2026-07-30',
            'amount' => 2200000,
            'penalty' => 0,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('invoices', [
            'user_id' => $santri->id,
            'name' => 'SPP Semester Ganjil 2026',
        ]);
    }

    public function test_invoice_creation_writes_audit_log_and_notification(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_permissions' => ['manage_invoices', 'view_audit_logs'],
        ]);
        $santri = User::factory()->create(['role' => 'santri', 'nis' => 'SPERM002']);

        $this->actingAs($admin)->postJson("/admin/santri/{$santri->id}/invoices", [
            'name' => 'SPP Semester Ganjil 2026',
            'due_date' => '2026-07-30',
            'amount' => 2200000,
            'penalty' => 50000,
        ])->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'invoice.created',
        ]);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $santri->id,
            'type' => 'invoice_created',
            'title' => 'Tagihan baru tersedia',
        ]);
    }

    public function test_admin_can_update_admin_permissions(): void
    {
        $owner = User::factory()->create([
            'role' => 'admin',
            'admin_permissions' => ['manage_permissions', 'view_audit_logs'],
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_permissions' => ['view_audit_logs'],
        ]);

        $response = $this->actingAs($owner)->putJson("/admin/users/{$admin->id}/permissions", [
            'permissions' => ['manage_santri', 'manage_invoices'],
        ]);

        $response->assertOk();
        $this->assertSame(['manage_santri', 'manage_invoices'], $admin->fresh()->admin_permissions);
        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $owner->id,
            'action' => 'admin.permissions_updated',
        ]);
    }

    public function test_santri_can_read_notifications(): void
    {
        $santri = User::factory()->create(['role' => 'santri', 'nis' => 'SPERM003']);
        UserNotification::create([
            'user_id' => $santri->id,
            'type' => 'invoice_created',
            'title' => 'Tagihan baru tersedia',
            'message' => 'Tagihan SPP tersedia.',
        ]);

        $this->actingAs($santri)
            ->getJson('/notifications')
            ->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('data.0.title', 'Tagihan baru tersedia');

        $this->actingAs($santri)
            ->postJson('/notifications/read-all')
            ->assertOk();

        $this->assertEquals(0, UserNotification::where('user_id', $santri->id)->whereNull('read_at')->count());
    }

    public function test_demo_seeder_is_idempotent(): void
    {
        $this->seed(\Database\Seeders\InvoiceSeeder::class);
        $this->seed(\Database\Seeders\InvoiceSeeder::class);

        $this->assertEquals(1, User::where('email', 'admin@syajagad.local')->count());
        $this->assertEquals(1, User::where('nis', '24 000 001')->count());
        $this->assertEquals(3, Invoice::whereHas('user', fn ($query) => $query->where('nis', '24 000 001'))->count());
    }
}
