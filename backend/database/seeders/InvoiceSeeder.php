<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate([
            'email' => 'admin@syajagad.local',
        ], [
            'name' => 'Admin SyaJagad',
            'password' => bcrypt('AdminJagad2026!'),
            'role' => 'admin',
            'username' => 'admin_sya',
            'nis' => null,
            'tgl_lahir' => null,
            'alamat' => 'Jl. Pusat Pesantren No. 1',
            'admin_permissions' => null,
        ]);

        $santri = User::updateOrCreate([
            'email' => 'ahmad.santoso@santri.syajagad.local',
        ], [
            'name' => 'Ahmad Santoso',
            'password' => bcrypt('Jagad001'),
            'role' => 'santri',
            'santri_status' => 'aktif',
            'username' => 'ahmad_santri',
            'nis' => '24 000 001',
            'gender' => 'Laki-laki',
            'tgl_lahir' => '2008-05-15',
            'alamat' => 'Jl. Pesantren Al-Hikmah No. 123, Jakarta',
            'created_at' => '2024-07-01 08:00:00',
        ]);

        Invoice::where('user_id', $santri->id)
            ->whereIn('due_date', ['2026-04-15', '2026-05-15'])
            ->delete();

        Invoice::updateOrCreate([
            'user_id' => $santri->id,
            'due_date' => '2026-01-15',
        ], [
            'user_id' => $santri->id,
            'name' => 'SPP Semester Genap 2026',
            'description' => 'Pembayaran SPP semester genap 2026',
            'due_date' => '2026-01-15',
            'amount' => 2200000,
            'penalty' => 200000,
            'total' => 2400000,
            'status' => 'terlambat',
        ]);

        Invoice::updateOrCreate([
            'user_id' => $santri->id,
            'due_date' => '2026-07-15',
        ], [
            'user_id' => $santri->id,
            'name' => 'SPP Semester Ganjil 2026',
            'description' => 'Pembayaran SPP semester ganjil 2026',
            'due_date' => '2026-07-15',
            'amount' => 2200000,
            'penalty' => 0,
            'total' => 2200000,
            'status' => 'belum',
        ]);

        Invoice::updateOrCreate([
            'user_id' => $santri->id,
            'due_date' => '2025-07-15',
        ], [
            'user_id' => $santri->id,
            'name' => 'SPP Semester Ganjil 2025',
            'description' => 'Pembayaran SPP semester ganjil 2025',
            'due_date' => '2025-07-15',
            'amount' => 2200000,
            'penalty' => 0,
            'total' => 2200000,
            'status' => 'lunas',
            'payment_method' => 'qris',
            'paid_date' => '2025-07-20',
        ]);
    }
}
