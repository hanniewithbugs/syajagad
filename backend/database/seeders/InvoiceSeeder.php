<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    use WithoutModelEvents;

    private const SEMESTER_FEE = 2200000;

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

        $students = [];

        foreach ($this->demoSantri() as $record) {
            $email = $record['email'];
            $nis = $record['nis'];
            $username = $record['username'];

            $student = User::where('role', 'santri')
                ->where(function ($query) use ($email, $nis, $username) {
                    $query->where('email', $email)
                        ->orWhere('nis', $nis)
                        ->orWhere('username', $username);
                })
                ->first() ?? new User();

            $student->fill([
                'name' => $record['name'],
                'password' => bcrypt($record['password']),
                'role' => 'santri',
                'santri_status' => $record['status'] ?? 'aktif',
                'username' => $username,
                'email' => $email,
                'nis' => $nis,
                'gender' => $record['gender'],
                'tgl_lahir' => $record['birthdate'],
                'alamat' => $record['address'],
                'created_at' => "{$record['entry_year']}-07-01 08:00:00",
                'updated_at' => now(),
            ]);
            $student->save();

            $students[$record['slug']] = $student;
        }

        User::where('role', 'santri')
            ->where('email', 'like', '%@santri.syajagad.local')
            ->whereNotIn('id', collect($students)->pluck('id'))
            ->delete();

        Invoice::whereIn('user_id', collect($students)->pluck('id'))->delete();

        $this->createInvoice($students['abdi.fysabilillah'], '2026-01-15', 'lunas', 0, 'qris', '2026-01-12');
        $this->createInvoice($students['abdi.fysabilillah'], '2026-07-15', 'belum');

        $this->createInvoice($students['elvina.virgawati'], '2026-01-15', 'lunas', 0, 'bca_va', '2026-01-14');

        $this->createInvoice($students['m.sholihul.munir'], '2026-01-15', 'terlambat', 250000);
    }

    private function createInvoice(User $santri, string $dueDate, string $status, int $penalty = 0, ?string $method = null, ?string $paidDate = null): void
    {
        $invoiceName = $this->semesterInvoiceName($dueDate);

        Invoice::updateOrCreate([
            'user_id' => $santri->id,
            'due_date' => $dueDate,
        ], [
            'user_id' => $santri->id,
            'name' => $invoiceName,
            'description' => "Pembayaran {$invoiceName}",
            'due_date' => $dueDate,
            'amount' => self::SEMESTER_FEE,
            'penalty' => $penalty,
            'total' => self::SEMESTER_FEE + $penalty,
            'status' => $status,
            'payment_method' => $method,
            'paid_date' => $paidDate,
        ]);
    }

    private function semesterInvoiceName(string $dueDate): string
    {
        $month = (int) date('n', strtotime($dueDate));
        $year = date('Y', strtotime($dueDate));
        $semester = $month === 7 ? 'Ganjil' : 'Genap';

        return "SPP Semester {$semester} {$year}";
    }

    private function demoSantri(): array
    {
        return [
            ['slug' => 'abdi.fysabilillah', 'name' => 'Abdi Fysabilillah', 'nis' => '24 008 847', 'email' => 'abdifysabilillah@gmail.com', 'username' => 'abdifysabilillah847', 'password' => 'Jagad847', 'gender' => 'Laki-laki', 'entry_year' => 2024, 'birthdate' => '2005-09-03', 'address' => 'Tuban'],
            ['slug' => 'elvina.virgawati', 'name' => 'Elvina Virgawati', 'nis' => '23 007 782', 'email' => 'elvinavirgawati@gmail.com', 'username' => 'elvinavirgawati782', 'password' => 'Jagad782', 'gender' => 'Perempuan', 'entry_year' => 2023, 'birthdate' => '2004-09-20', 'address' => 'Pasuruan'],
            ['slug' => 'm.sholihul.munir', 'name' => 'M Sholihul Munir', 'nis' => '24 009 850', 'email' => 'msholihulmunir@gmail.com', 'username' => 'msholihulmunir850', 'password' => 'Jagad850', 'gender' => 'Laki-laki', 'entry_year' => 2024, 'birthdate' => '2005-11-08', 'address' => 'Gresik'],
            ['slug' => 'afrizal.nur.kadir', 'name' => 'Afrizal Nur Kadir', 'nis' => '24 009 851', 'email' => 'afrizalnurkadir@gmail.com', 'username' => 'afrizalnurkadir851', 'password' => 'Jagad851', 'gender' => 'Laki-laki', 'entry_year' => 2024, 'birthdate' => '1999-02-03', 'address' => 'Jakarta'],
            ['slug' => 'inayaturrobbaniyah', 'name' => 'Inayaturrobbaniyah', 'nis' => '24 009 852', 'email' => 'inayaturrobbaniyah@gmail.com', 'username' => 'inayaturrobbaniyah852', 'password' => 'Jagad852', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2001-07-05', 'address' => 'Rumbia'],
            ['slug' => 'alvia.cahya.suci', 'name' => 'Alvia Cahya Suci', 'nis' => '24 007 819', 'email' => 'alviacahyasuci@gmail.com', 'username' => 'alviacahyasuci819', 'password' => 'Jagad819', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2003-09-15', 'address' => 'Magelang'],
            ['slug' => 'ameliya.iffa.zihana', 'name' => 'Ameliya Iffa Zihana', 'nis' => '24 007 823', 'email' => 'ameliyaiffazihana@gmail.com', 'username' => 'ameliyaiffazihana823', 'password' => 'Jagad823', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2006-03-11', 'address' => 'Tuban'],
            ['slug' => 'anggalih.sayekti', 'name' => 'Anggalih Sayekti', 'nis' => '24 007 820', 'email' => 'anggalihsayekti@gmail.com', 'username' => 'anggalihsayekti820', 'password' => 'Jagad820', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2006-06-07', 'address' => 'Ngawi'],
            ['slug' => 'athiyyatus.salisah', 'name' => 'Athiyyatus Salisah', 'nis' => '24 008 845', 'email' => 'athiyyatussalisah@gmail.com', 'username' => 'athiyyatussalisah845', 'password' => 'Jagad845', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2006-11-24', 'address' => 'Bojonegoro'],
            ['slug' => 'azahra.aprinandita', 'name' => 'Azahra Aprinandita', 'nis' => '24 006 814', 'email' => 'azahraaprinandita@gmail.com', 'username' => 'azahraaprinandita814', 'password' => 'Jagad814', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2006-04-25', 'address' => 'Tulungagung'],
            ['slug' => 'binti.aminatul.hidayah', 'name' => 'Binti Aminatul Hidayah', 'nis' => '24 007 836', 'email' => 'bintiaminatulhidayah@gmail.com', 'username' => 'bintiaminatulhidayah836', 'password' => 'Jagad836', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2005-07-13', 'address' => 'Gresik'],
            ['slug' => 'diah.pratiwi', 'name' => 'Diah Pratiwi', 'nis' => '24 007 839', 'email' => 'diahpratiwi@gmail.com', 'username' => 'diahpratiwi839', 'password' => 'Jagad839', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2006-08-12', 'address' => 'Blitar'],
            ['slug' => 'fira.rahma.mutiahana', 'name' => 'Fira Rahma Mutiahana', 'nis' => '24 007 830', 'email' => 'firarahmamutiahana@gmail.com', 'username' => 'firarahmamutiahana830', 'password' => 'Jagad830', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2005-12-07', 'address' => 'Jombang'],
            ['slug' => 'hibatin.wafiroh.ulil.azizah', 'name' => 'Hibatin Wafiroh Ulil Azizah', 'nis' => '24 007 816', 'email' => 'hibatinwafirohulilazizah@gmail.com', 'username' => 'hibatinwafirohulilazizah816', 'password' => 'Jagad816', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2005-05-21', 'address' => 'Lamongan'],
            ['slug' => 'intan.nur.aini', 'name' => 'Intan Nur Aini', 'nis' => '24 007 834', 'email' => 'intannuraini@gmail.com', 'username' => 'intannuraini834', 'password' => 'Jagad834', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2006-04-12', 'address' => 'Pasuruan'],
            ['slug' => 'kamaliatul.hasanah', 'name' => 'Kamaliatul Hasanah', 'nis' => '24 007 833', 'email' => 'kamaliatulhasanah@gmail.com', 'username' => 'kamaliatulhasanah833', 'password' => 'Jagad833', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2004-03-20', 'address' => 'Lamongan'],
            ['slug' => 'kheira.emila.al.quran', 'name' => "Kheira Emila Al Qur'an", 'nis' => '24 008 844', 'email' => 'kheiraemilaalquran@gmail.com', 'username' => 'kheiraemilaalquran844', 'password' => 'Jagad844', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2005-06-26', 'address' => 'Surabaya'],
            ['slug' => 'muhammad.khairul.anam', 'name' => 'Muhammad Khairul Anam', 'nis' => '24 007 832', 'email' => 'muhammadkhairulanam@gmail.com', 'username' => 'muhammadkhairulanam832', 'password' => 'Jagad832', 'gender' => 'Laki-laki', 'entry_year' => 2024, 'birthdate' => '2006-10-22', 'address' => 'Lamongan'],
            ['slug' => 'nadila.alfina.nur.fitriana', 'name' => 'Nadila Alfina Nur Fitriana', 'nis' => '24 007 821', 'email' => 'nadilaalfinanurfitriana@gmail.com', 'username' => 'nadilaalfinanurfitriana821', 'password' => 'Jagad821', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2006-09-07', 'address' => 'Tuban'],
            ['slug' => 'nisrina.lathifatul.fakhriyah', 'name' => 'Nisrina Lathifatul Fakhriyah', 'nis' => '24 006 813', 'email' => 'nisrinalathifatulfakhriyah@gmail.com', 'username' => 'nisrinalathifatulfakhriyah813', 'password' => 'Jagad813', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2006-01-09', 'address' => 'Malang'],
            ['slug' => 'sofia.aqillah', 'name' => 'Sofia Aqillah', 'nis' => '24 007 815', 'email' => 'sofiaaqillah@gmail.com', 'username' => 'sofiaaqillah815', 'password' => 'Jagad815', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2006-10-10', 'address' => 'Sidoarjo'],
            ['slug' => 'sulthon.shokhibul.ibrahim', 'name' => 'Sulthon Shokhibul Ibrahim', 'nis' => '24 007 841', 'email' => 'sulthonshokhibulibrahim@gmail.com', 'username' => 'sulthonshokhibulibrahim841', 'password' => 'Jagad841', 'gender' => 'Laki-laki', 'entry_year' => 2024, 'birthdate' => '2003-04-19', 'address' => 'Mojokerto'],
            ['slug' => 'yusrin.lutfi.afifah', 'name' => 'Yusrin Lutfi Afifah', 'nis' => '24 008 848', 'email' => 'yusrinlutfiafifah@gmail.com', 'username' => 'yusrinlutfiafifah848', 'password' => 'Jagad848', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2006-07-22', 'address' => 'Tuban'],
            ['slug' => 'nuril.fathurin', 'name' => 'Nuril Fathurin', 'nis' => '21 009 084', 'email' => 'nurilfathurin@gmail.com', 'username' => 'nurilfathurin084', 'password' => 'Jagad084', 'gender' => 'Perempuan', 'entry_year' => 2021, 'birthdate' => '1998-07-16', 'address' => 'Lamongan'],
            ['slug' => 'oki.ciputri', 'name' => 'Oki Ciputri', 'nis' => '21 009 095', 'email' => 'okiciputri@gmail.com', 'username' => 'okiciputri095', 'password' => 'Jagad095', 'gender' => 'Perempuan', 'entry_year' => 2021, 'birthdate' => '2001-09-22', 'address' => 'Sidoarjo'],
            ['slug' => 'oki.safitri', 'name' => 'Oki Safitri', 'nis' => '21 009 096', 'email' => 'okisafitri@gmail.com', 'username' => 'okisafitri096', 'password' => 'Jagad096', 'gender' => 'Perempuan', 'entry_year' => 2021, 'birthdate' => '2001-09-22', 'address' => 'Sidoarjo'],
            ['slug' => 'm.zainal.abidin', 'name' => 'M Zainal Abidin', 'nis' => '21 009 081', 'email' => 'mzainalabidin@gmail.com', 'username' => 'mzainalabidin081', 'password' => 'Jagad081', 'gender' => 'Laki-laki', 'entry_year' => 2021, 'birthdate' => '1998-01-02', 'address' => 'Gresik'],
            ['slug' => 'muhammad.khoirul.fikri', 'name' => 'Muhammad Khoirul Fikri', 'nis' => '22 009 090', 'email' => 'muhammadkhoirulfikri@gmail.com', 'username' => 'muhammadkhoirulfikri090', 'password' => 'Jagad090', 'gender' => 'Laki-laki', 'entry_year' => 2022, 'birthdate' => '1999-07-09', 'address' => 'Madura'],
            ['slug' => 'gagah.ibnu.muthoilah', 'name' => "Gagah Ibnu Mutho'ilah", 'nis' => '21 009 099', 'email' => 'gagahibnumuthoilah@gmail.com', 'username' => 'gagahibnumuthoilah099', 'password' => 'Jagad099', 'gender' => 'Laki-laki', 'entry_year' => 2021, 'birthdate' => '2000-12-03', 'address' => 'Jombang'],
            ['slug' => 'rahmat.catur.abdian', 'name' => 'Rahmat Catur Abdian', 'nis' => '21 009 082', 'email' => 'rahmatcaturabdian@gmail.com', 'username' => 'rahmatcaturabdian082', 'password' => 'Jagad082', 'gender' => 'Laki-laki', 'entry_year' => 2021, 'birthdate' => '1998-10-29', 'address' => 'Lamongan'],
            ['slug' => 'farhan.maulana', 'name' => 'Farhan Maulana', 'nis' => '24 009 853', 'email' => 'farhanmaulana@gmail.com', 'username' => 'farhanmaulana853', 'password' => 'Jagad853', 'gender' => 'Laki-laki', 'entry_year' => 2024, 'birthdate' => '2005-02-16', 'address' => 'Surabaya'],
            ['slug' => 'siti.nur.khalimah', 'name' => 'Siti Nur Khalimah', 'nis' => '24 009 854', 'email' => 'sitinurkhalimah@gmail.com', 'username' => 'sitinurkhalimah854', 'password' => 'Jagad854', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2005-05-25', 'address' => 'Sidoarjo'],
            ['slug' => 'rafi.aditya.pratama', 'name' => 'Rafi Aditya Pratama', 'nis' => '24 009 855', 'email' => 'rafiadityapratama@gmail.com', 'username' => 'rafiadityapratama855', 'password' => 'Jagad855', 'gender' => 'Laki-laki', 'entry_year' => 2024, 'birthdate' => '2005-08-18', 'address' => 'Surabaya'],
            ['slug' => 'laila.fitria.ramadhani', 'name' => 'Laila Fitria Ramadhani', 'nis' => '24 009 856', 'email' => 'lailafitriaramadhani@gmail.com', 'username' => 'lailafitriaramadhani856', 'password' => 'Jagad856', 'gender' => 'Perempuan', 'entry_year' => 2024, 'birthdate' => '2005-10-10', 'address' => 'Surabaya'],
            ['slug' => 'dimas.arya.wicaksana', 'name' => 'Dimas Arya Wicaksana', 'nis' => '24 009 857', 'email' => 'dimasaryawicaksana@gmail.com', 'username' => 'dimasaryawicaksana857', 'password' => 'Jagad857', 'gender' => 'Laki-laki', 'entry_year' => 2024, 'birthdate' => '2005-02-28', 'address' => 'Surabaya'],
        ];
    }
}
