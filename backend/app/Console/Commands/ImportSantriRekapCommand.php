<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportSantriRekapCommand extends Command
{
    protected $signature = 'santri:import-rekap
        {file : Path file rekap_santri.xlsx atau CSV}
        {--dry-run : Preview tanpa menyimpan}
        {--replace-demo : Hapus data santri demo lokal yang tidak ada di file}
        {--with-payment-study-case : Buat skenario tagihan natural untuk beberapa santri di file}';

    protected $description = 'Import data santri dari file rekap Excel resmi.';

    private const GENDER_OVERRIDES = [
        'abdi fysabilillah' => 'Laki-laki',
        'elvina virgawati' => 'Perempuan',
        'm sholihul munir' => 'Laki-laki',
        'afrizal nur kadir' => 'Laki-laki',
        'inayaturrobbaniyah' => 'Perempuan',
        'alvia cahya suci' => 'Perempuan',
        'ameliya iffa zihana' => 'Perempuan',
        'anggalih sayekti' => 'Perempuan',
        'athiyyatus salisah' => 'Perempuan',
        'azahra aprinandita' => 'Perempuan',
        'binti aminatul hidayah' => 'Perempuan',
        'diah pratiwi' => 'Perempuan',
        'fira rahma mutiahana' => 'Perempuan',
        'hibatin wafiroh ulil azizah' => 'Perempuan',
        'intan nur aini' => 'Perempuan',
        'kamaliatul hasanah' => 'Perempuan',
        'kheira emila al qur\'an' => 'Perempuan',
        'muhammad khairul anam' => 'Laki-laki',
        'nadila alfina nur fitriana' => 'Perempuan',
        'nisrina lathifatul fakhriyah' => 'Perempuan',
        'sofia aqillah' => 'Perempuan',
        'sulthon shokhibul ibrahim' => 'Laki-laki',
        'yusrin lutfi afifah' => 'Perempuan',
        'nuril fathurin' => 'Perempuan',
        'oki ciputri' => 'Perempuan',
        'oki safitri' => 'Perempuan',
        'm zainal abidin' => 'Laki-laki',
        'muhammad khoirul fikri' => 'Laki-laki',
        'gagah ibnu mutho\'ilah' => 'Laki-laki',
        'rahmat catur abdian' => 'Laki-laki',
    ];

    private const MONTHS = [
        'januari' => 1,
        'februari' => 2,
        'maret' => 3,
        'april' => 4,
        'mei' => 5,
        'juni' => 6,
        'juli' => 7,
        'agustus' => 8,
        'september' => 9,
        'oktober' => 10,
        'november' => 11,
        'desember' => 12,
    ];

    public function handle(): int
    {
        $file = (string) $this->argument('file');
        $dryRun = (bool) $this->option('dry-run');

        if (! is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return self::FAILURE;
        }

        $rows = Str::of($file)->lower()->endsWith('.csv')
            ? $this->readCsv($file)
            : $this->readXlsx($file);
        $records = $this->buildRecords($rows);

        if ($records === []) {
            $this->warn('Tidak ada data santri yang bisa diimport.');
            return self::SUCCESS;
        }

        $this->table(
            ['Nama', 'NIS/NIP', 'Kelamin', 'Angkatan', 'Tanggal Lahir', 'Username', 'Password'],
            array_map(fn (array $record) => [
                $record['name'],
                $record['nis'],
                $record['gender'],
                $record['entry_year'],
                $record['tgl_lahir'],
                $record['username'],
                $record['plain_password'],
            ], $records)
        );

        if ($dryRun) {
            $this->info('Dry-run selesai. Tidak ada data yang disimpan.');
            return self::SUCCESS;
        }

        if ((bool) $this->option('replace-demo')) {
            $this->deleteDemoStudentsNotIn($records);
        }

        $created = 0;
        $updated = 0;

        foreach ($records as $record) {
            $user = User::query()
                ->where('role', 'santri')
                ->where(function ($query) use ($record) {
                    $query->where('nis', $record['nis'])
                        ->orWhereRaw("REPLACE(nis, ' ', '') = ?", [$record['nis_compact']])
                        ->orWhere('email', $record['email'])
                        ->orWhere('username', $record['username']);
                })
                ->first();

            if (! $user) {
                $user = new User();
                $user->role = 'santri';
                $created++;
            } else {
                $updated++;
            }

            $user->fill([
                'nis' => $record['nis'],
                'name' => $record['name'],
                'gender' => $record['gender'],
                'tgl_lahir' => $record['tgl_lahir'],
                'alamat' => $record['alamat'],
                'email' => $record['email'],
                'username' => $record['username'],
                'password' => Hash::make($record['plain_password']),
            ]);
            $user->created_at = $user->exists && $user->created_at
                ? $user->created_at
                : Carbon::create($record['entry_year'], 7, 1, 8, 0, 0);
            $user->updated_at = now();
            $user->save();
        }

        if ((bool) $this->option('with-payment-study-case')) {
            $this->syncPaymentStudyCase($records);
        }

        $credentialPath = storage_path('app/santri-credentials-' . now()->format('Ymd-His') . '.csv');
        $this->writeCredentials($credentialPath, $records);

        $this->info("Import selesai. {$created} dibuat, {$updated} diperbarui.");
        if ((bool) $this->option('with-payment-study-case')) {
            $this->info('Study case pembayaran dibuat: 2 invoice lunas, 1 belum bayar, dan 1 tunggakan.');
        }
        $this->info("File kredensial: {$credentialPath}");

        return self::SUCCESS;
    }

    private function buildRecords(array $rows): array
    {
        $records = [];
        $headers = array_map(fn ($value) => Str::of((string) $value)->lower()->trim()->value(), array_shift($rows) ?? []);

        foreach ($rows as $row) {
            $data = array_combine($headers, array_pad($row, count($headers), null));
            $name = trim((string) ($data['nama'] ?? ''));
            $nis = $this->normalizeNis((string) ($data['nis/nip'] ?? ''));
            $email = trim((string) ($data['email'] ?? ''));

            if ($name === '' || $nis === '' || $email === '') {
                continue;
            }

            $nisCompact = preg_replace('/\D+/', '', $nis);
            $entryYear = $this->entryYear((string) ($data['angkatan'] ?? ''), $nisCompact);
            $username = trim((string) ($data['username'] ?? ''));
            $password = trim((string) ($data['password'] ?? ''));

            $records[] = [
                'name' => $name,
                'nis' => $nis,
                'nis_compact' => $nisCompact,
                'gender' => $this->normalizeGender((string) ($data['kelamin'] ?? ''), $name),
                'entry_year' => $entryYear,
                'tgl_lahir' => $this->parseDate((string) ($data['tanggal lahir'] ?? '')),
                'alamat' => trim((string) ($data['alamat'] ?? '')),
                'email' => $email,
                'username' => $username !== '' ? $username : $this->buildUsername($name, $nisCompact),
                'plain_password' => $password !== '' ? $password : 'Jagad' . substr($nisCompact, -3),
            ];
        }

        return $records;
    }

    private function deleteDemoStudentsNotIn(array $records): void
    {
        $emails = array_column($records, 'email');
        $nisValues = array_column($records, 'nis');
        $usernames = array_column($records, 'username');

        User::where('role', 'santri')
            ->where(function ($query) {
                $query->where('email', 'like', '%@santri.syajagad.local')
                    ->orWhere('email', 'like', '%@santri.syajagad.ac.id');
            })
            ->whereNotIn('email', $emails)
            ->whereNotIn('nis', $nisValues)
            ->whereNotIn('username', $usernames)
            ->delete();
    }

    private function syncPaymentStudyCase(array $records): void
    {
        $recordsByName = collect($records)->keyBy('name');
        $cases = [
            'Abdi Fysabilillah' => [
                ['due_date' => '2026-01-15', 'status' => 'lunas', 'penalty' => 0, 'payment_method' => 'qris', 'paid_date' => '2026-01-12'],
                ['due_date' => '2026-07-15', 'status' => 'belum', 'penalty' => 0, 'payment_method' => null, 'paid_date' => null],
            ],
            'Elvina Virgawati' => [
                ['due_date' => '2026-01-15', 'status' => 'lunas', 'penalty' => 0, 'payment_method' => 'bca_va', 'paid_date' => '2026-01-14'],
            ],
            'M Sholihul Munir' => [
                ['due_date' => '2026-01-15', 'status' => 'terlambat', 'penalty' => 250000, 'payment_method' => null, 'paid_date' => null],
            ],
        ];

        foreach ($cases as $name => $invoices) {
            $record = $recordsByName->get($name);
            if (! $record) {
                continue;
            }

            $santri = User::where('role', 'santri')
                ->where(function ($query) use ($record) {
                    $query->where('nis', $record['nis'])
                        ->orWhere('email', $record['email'])
                        ->orWhere('username', $record['username']);
                })
                ->first();

            if (! $santri) {
                continue;
            }

            foreach ($invoices as $invoice) {
                $name = $this->semesterInvoiceName($invoice['due_date']);
                $amount = 2200000;

                Invoice::updateOrCreate([
                    'user_id' => $santri->id,
                    'due_date' => $invoice['due_date'],
                ], [
                    'user_id' => $santri->id,
                    'name' => $name,
                    'description' => "Pembayaran {$name}",
                    'due_date' => $invoice['due_date'],
                    'amount' => $amount,
                    'penalty' => $invoice['penalty'],
                    'total' => $amount + $invoice['penalty'],
                    'status' => $invoice['status'],
                    'payment_method' => $invoice['payment_method'],
                    'paid_date' => $invoice['paid_date'],
                ]);
            }
        }
    }

    private function semesterInvoiceName(string $dueDate): string
    {
        $date = Carbon::parse($dueDate);
        $semester = (int) $date->month === 7 ? 'Ganjil' : 'Genap';

        return "SPP Semester {$semester} {$date->year}";
    }

    private function readXlsx(string $file): array
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('Ekstensi PHP ZipArchive belum aktif. Konversi file Excel ke CSV lalu jalankan command ini dengan file CSV.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($file) !== true) {
            throw new \RuntimeException('File Excel tidak bisa dibuka.');
        }

        $sharedStrings = $this->readSharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new \RuntimeException('Sheet pertama tidak ditemukan.');
        }

        $sheet = simplexml_load_string($sheetXml);
        $rows = [];

        foreach ($sheet->sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                $index = $this->columnIndex($reference);
                $values[$index] = $this->cellValue($cell, $sharedStrings);
            }
            ksort($values);
            $max = $values === [] ? -1 : max(array_keys($values));
            $rows[] = array_map(fn ($index) => $values[$index] ?? null, range(0, $max));
        }

        return $rows;
    }

    private function readCsv(string $file): array
    {
        $handle = fopen($file, 'r');
        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function readSharedStrings(\ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $shared = simplexml_load_string($xml);
        $strings = [];

        foreach ($shared->si as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;
                continue;
            }

            $parts = [];
            foreach ($item->r as $run) {
                $parts[] = (string) $run->t;
            }
            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function cellValue(\SimpleXMLElement $cell, array $sharedStrings): ?string
    {
        $type = (string) $cell['t'];

        if ($type === 's') {
            return $sharedStrings[(int) $cell->v] ?? null;
        }

        if ($type === 'inlineStr') {
            return (string) $cell->is->t;
        }

        return isset($cell->v) ? (string) $cell->v : null;
    }

    private function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/', $reference, $matches);
        $letters = $matches[0] ?? 'A';
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function normalizeNis(string $nis): string
    {
        $digits = preg_replace('/\D+/', '', $nis);
        if (strlen($digits) === 8) {
            return substr($digits, 0, 2) . ' ' . substr($digits, 2, 3) . ' ' . substr($digits, 5, 3);
        }

        return trim(preg_replace('/\s+/', ' ', $nis));
    }

    private function entryYearFromNis(string $nisCompact): int
    {
        $prefix = (int) substr($nisCompact, 0, 2);
        return $prefix >= 70 ? 1900 + $prefix : 2000 + $prefix;
    }

    private function entryYear(string $value, string $nisCompact): int
    {
        $year = (int) trim($value);

        return $year >= 1900 && $year <= 2100
            ? $year
            : $this->entryYearFromNis($nisCompact);
    }

    private function parseDate(string $date): string
    {
        $date = trim($date);

        foreach (['n/j/Y', 'm/d/Y', 'Y-m-d', 'd/m/Y'] as $format) {
            $parsed = Carbon::createFromFormat($format, $date);
            if ($parsed !== false) {
                return $parsed->format('Y-m-d');
            }
        }

        return $this->parseIndonesianDate($date);
    }

    private function parseIndonesianDate(string $date): string
    {
        $parts = preg_split('/\s+/', strtolower(trim($date)));
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException("Tanggal lahir tidak valid: {$date}");
        }

        return Carbon::create((int) $parts[2], self::MONTHS[$parts[1]], (int) $parts[0])->format('Y-m-d');
    }

    private function normalizeGender(string $gender, string $name): string
    {
        $gender = Str::of($gender)->lower()->squish()->value();

        return match ($gender) {
            'laki-laki', 'laki laki', 'l', 'pria' => 'Laki-laki',
            'perempuan', 'p', 'wanita' => 'Perempuan',
            default => $this->inferGender($name),
        };
    }

    private function inferGender(string $name): string
    {
        $key = Str::of($name)
            ->lower()
            ->replace(['’', '`'], "'")
            ->squish()
            ->value();

        return self::GENDER_OVERRIDES[$key] ?? 'Perempuan';
    }

    private function buildUsername(string $name, string $nisCompact): string
    {
        $base = Str::slug($name, '');
        return substr($base, 0, 35) . substr($nisCompact, -3);
    }

    private function writeCredentials(string $path, array $records): void
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, ['Nama', 'NIS/NIP', 'Email', 'Username', 'Password', 'Kelamin', 'Angkatan', 'Tanggal Lahir', 'Alamat']);

        foreach ($records as $record) {
            fputcsv($handle, [
                $record['name'],
                $record['nis'],
                $record['email'],
                $record['username'],
                $record['plain_password'],
                $record['gender'],
                $record['entry_year'],
                $record['tgl_lahir'],
                $record['alamat'],
            ]);
        }

        fclose($handle);
    }
}
