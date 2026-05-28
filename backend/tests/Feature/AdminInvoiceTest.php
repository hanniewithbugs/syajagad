<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_invoice_for_santri(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $santri = User::factory()->create([
            'role' => 'santri',
            'nis' => 'SINV001',
        ]);

        $response = $this->actingAs($admin)->postJson("/admin/santri/{$santri->id}/invoices", [
            'name' => 'SPP Semester Ganjil 2026',
            'description' => 'Tagihan SPP semester ganjil',
            'due_date' => '2026-07-15',
            'amount' => 2200000,
            'penalty' => 100000,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.total', 2300000)
            ->assertJsonPath('data.status', 'belum');

        $this->assertDatabaseHas('invoices', [
            'user_id' => $santri->id,
            'name' => 'SPP Semester Ganjil 2026',
            'amount' => 2200000,
            'penalty' => 100000,
            'total' => 2300000,
        ]);
    }

    public function test_admin_can_create_bulk_invoices_and_skip_duplicates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $firstSantri = User::factory()->create(['role' => 'santri', 'nis' => 'SINV002']);
        $secondSantri = User::factory()->create(['role' => 'santri', 'nis' => 'SINV003']);

        Invoice::create([
            'user_id' => $firstSantri->id,
            'name' => 'SPP Semester Ganjil 2026',
            'description' => 'Tagihan lama',
            'due_date' => '2026-07-15',
            'amount' => 2200000,
            'penalty' => 0,
            'total' => 2200000,
            'status' => 'belum',
        ]);

        $response = $this->actingAs($admin)->postJson('/admin/invoices/bulk', [
            'name' => 'SPP Semester Ganjil 2026',
            'due_date' => '2026-07-15',
            'amount' => 2200000,
            'penalty' => 0,
        ]);

        $response->assertCreated()
            ->assertJsonPath('created', 1)
            ->assertJsonPath('skipped', 1);

        $this->assertEquals(1, $firstSantri->invoices()->where('name', 'SPP Semester Ganjil 2026')->count());
        $this->assertEquals(1, $secondSantri->invoices()->where('name', 'SPP Semester Ganjil 2026')->count());
    }

    public function test_admin_invoice_due_date_must_be_january_or_july(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $santri = User::factory()->create(['role' => 'santri', 'nis' => 'SINV009']);

        $this->actingAs($admin)->postJson("/admin/santri/{$santri->id}/invoices", [
            'name' => 'SPP Semester Tidak Valid',
            'due_date' => '2026-05-15',
            'amount' => 2200000,
            'penalty' => 0,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('due_date');
    }

    public function test_admin_stats_include_payment_risk_insights(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $santri = User::factory()->create(['role' => 'santri', 'nis' => 'SINV004']);

        Invoice::create([
            'user_id' => $santri->id,
            'name' => 'SPP Januari 2026',
            'due_date' => '2026-01-10',
            'amount' => 2200000,
            'penalty' => 150000,
            'total' => 2350000,
            'status' => 'terlambat',
        ]);

        $response = $this->actingAs($admin)->getJson('/admin/stats');

        $response->assertOk()
            ->assertJsonPath('highRiskSantri', 1)
            ->assertJsonPath('topRiskSantri.0.name', $santri->name)
            ->assertJsonPath('topRiskSantri.0.risk_label', 'Tinggi');
    }

    public function test_admin_stats_and_payments_include_all_santri_and_synced_outstanding(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $paidSantri = User::factory()->create(['role' => 'santri', 'nis' => 'SINV005']);
        $lateSantri = User::factory()->create(['role' => 'santri', 'nis' => 'SINV006']);
        $noInvoiceSantri = User::factory()->create(['role' => 'santri', 'nis' => 'SINV007']);

        Invoice::create([
            'user_id' => $paidSantri->id,
            'name' => 'SPP Semester Genap 2026',
            'due_date' => '2026-01-15',
            'amount' => 2200000,
            'penalty' => 0,
            'total' => 2200000,
            'status' => 'lunas',
            'paid_date' => now()->subDay()->format('Y-m-d'),
        ]);

        $lateInvoice = Invoice::create([
            'user_id' => $lateSantri->id,
            'name' => 'SPP Semester Ganjil 2026',
            'due_date' => '2026-01-15',
            'amount' => 2200000,
            'penalty' => 0,
            'total' => 2200000,
            'status' => 'belum',
        ]);

        $this->actingAs($admin)
            ->getJson('/admin/stats')
            ->assertOk()
            ->assertJsonPath('totalSantri', 3)
            ->assertJsonPath('totalPaid', 1)
            ->assertJsonPath('totalTunggak', 1)
            ->assertJsonPath('outstanding', $lateInvoice->fresh()->total);

        $payments = $this->actingAs($admin)
            ->getJson('/admin/payments')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->json('data');

        $this->assertContains('Belum Ada Tagihan', collect($payments)->pluck('status_label')->all());
    }

    public function test_admin_can_store_santri_cuti_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/admin/santri', [
            'name' => 'Santri Cuti',
            'nis' => 'SINV008',
            'gender' => 'Laki-laki',
            'email' => 'cuti@example.com',
            'username' => 'santri_cuti',
            'password' => 'secret123',
            'tgl_lahir' => '2008-01-10',
            'alamat' => 'Alamat Cuti',
            'santri_status' => 'cuti',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', [
            'nis' => 'SINV008',
            'santri_status' => 'cuti',
        ]);

        $this->actingAs($admin)
            ->getJson('/admin/santri')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'Cuti');
    }
}
