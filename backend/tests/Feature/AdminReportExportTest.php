<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_export_payment_report_as_csv_excel_and_printable_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $santri = User::factory()->create([
            'role' => 'santri',
            'nis' => '24 008 847',
            'gender' => 'Laki-laki',
        ]);

        Invoice::create([
            'user_id' => $santri->id,
            'name' => 'SPP Semester Ganjil 2026',
            'due_date' => '2026-07-15',
            'amount' => 2200000,
            'penalty' => 0,
            'total' => 2200000,
            'status' => 'belum',
        ]);

        $this->actingAs($admin)
            ->get('/admin/reports/export/csv')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertSee('SPP Semester Ganjil 2026');

        $this->actingAs($admin)
            ->get('/admin/reports/export/excel')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->assertSee('SPP Semester Ganjil 2026');

        $this->actingAs($admin)
            ->get('/admin/reports/export/pdf')
            ->assertOk()
            ->assertSee('Cetak / Simpan PDF')
            ->assertSee('Laporan Pembayaran SyaJagad');
    }
}
