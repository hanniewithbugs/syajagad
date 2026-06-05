<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class LocalChatbotService
{
    public const INTENTS = [
        'total_tagihan',
        'tagihan_aktif',
        'denda',
        'cara_bayar_qris',
        'cara_bayar_va',
        'status_terakhir',
        'rekomendasi',
        'kontak_admin',
    ];

    public function answer(User $user, ?string $intent, ?string $message = null): array
    {
        $intent = in_array($intent, self::INTENTS, true)
            ? $intent
            : $this->resolveIntent($message);

        $invoices = Invoice::where('user_id', $user->id)
            ->latest('due_date')
            ->get();

        $activeInvoices = $invoices
            ->filter(fn (Invoice $invoice) => $invoice->status !== 'lunas')
            ->sortBy('due_date')
            ->values();

        $message = match ($intent) {
            'total_tagihan' => $this->totalTagihan($activeInvoices),
            'tagihan_aktif' => $this->tagihanAktif($activeInvoices),
            'denda' => $this->denda($activeInvoices),
            'cara_bayar_qris' => $this->caraBayarQris(),
            'cara_bayar_va' => $this->caraBayarVa(),
            'status_terakhir' => $this->statusTerakhir($invoices),
            'rekomendasi' => $this->rekomendasi($activeInvoices),
            'kontak_admin' => $this->kontakAdmin(),
            default => $this->fallback(),
        };

        return [
            'intent' => $intent,
            'message' => $message,
            'summary' => [
                'active_invoice_count' => $activeInvoices->count(),
                'active_total' => $activeInvoices->sum(fn (Invoice $invoice) => $this->invoiceTotal($invoice)),
                'penalty_total' => $activeInvoices->sum(fn (Invoice $invoice) => (int) ($invoice->penalty ?? 0)),
            ],
        ];
    }

    private function resolveIntent(?string $message): string
    {
        $text = strtolower((string) $message);

        if ($text === '') {
            return 'fallback';
        }

        return match (true) {
            str_contains($text, 'denda') || str_contains($text, 'telat') || str_contains($text, 'terlambat') => 'denda',
            str_contains($text, 'qris') => 'cara_bayar_qris',
            str_contains($text, 'va') || str_contains($text, 'virtual account') || str_contains($text, 'transfer') || str_contains($text, 'bayar') => 'cara_bayar_va',
            str_contains($text, 'status') || str_contains($text, 'terakhir') || str_contains($text, 'lunas') => 'status_terakhir',
            str_contains($text, 'aktif') || str_contains($text, 'belum') || str_contains($text, 'tunggak') => 'tagihan_aktif',
            str_contains($text, 'berapa') || str_contains($text, 'total') || str_contains($text, 'tagihan') || str_contains($text, 'spp') => 'total_tagihan',
            str_contains($text, 'saran') || str_contains($text, 'rekomendasi') || str_contains($text, 'prioritas') => 'rekomendasi',
            str_contains($text, 'admin') || str_contains($text, 'kontak') || str_contains($text, 'hubungi') => 'kontak_admin',
            default => 'fallback',
        };
    }

    private function fallback(): string
    {
        return 'Saya belum memahami pertanyaan itu. Saya paling akurat untuk hal yang berhubungan dengan SyaJagad, seperti tagihan, denda, status pembayaran, cara bayar QRIS/VA, rekomendasi pelunasan, atau kontak admin.';
    }

    private function totalTagihan(Collection $activeInvoices): string
    {
        if ($activeInvoices->isEmpty()) {
            return 'Alhamdulillah, saat ini tidak ada tagihan aktif. Semua pembayaran sudah tercatat lunas.';
        }

        $total = $activeInvoices->sum(fn (Invoice $invoice) => $this->invoiceTotal($invoice));

        return "Total tagihan aktif kamu saat ini {$this->rupiah($total)} dari {$activeInvoices->count()} tagihan. Buka menu Tagihan untuk memilih pembayaran lewat Midtrans.";
    }

    private function tagihanAktif(Collection $activeInvoices): string
    {
        if ($activeInvoices->isEmpty()) {
            return 'Tidak ada tagihan belum lunas. Riwayat pembayaran bisa dicek di bagian Dashboard.';
        }

        $lines = $activeInvoices
            ->take(4)
            ->map(function (Invoice $invoice) {
                $dueDate = $this->formatDate($invoice->due_date);
                $status = $invoice->status === 'terlambat' ? 'terlambat' : 'belum lunas';

                return "- {$invoice->name}: {$this->rupiah($this->invoiceTotal($invoice))}, jatuh tempo {$dueDate}, status {$status}";
            })
            ->implode("\n");

        $more = $activeInvoices->count() > 4
            ? "\nMasih ada " . ($activeInvoices->count() - 4) . ' tagihan lain. Silakan buka menu Tagihan untuk daftar lengkap.'
            : '';

        return "Tagihan aktif kamu:\n{$lines}{$more}";
    }

    private function denda(Collection $activeInvoices): string
    {
        $penaltyTotal = $activeInvoices->sum(fn (Invoice $invoice) => (int) ($invoice->penalty ?? 0));

        if ($penaltyTotal <= 0) {
            return 'Saat ini tidak ada denda aktif. Tetap bayar sebelum jatuh tempo agar status administrasi aman.';
        }

        return "Total denda aktif kamu {$this->rupiah($penaltyTotal)}. Denda dihitung berjalan per bulan setelah jatuh tempo semester, dan sudah masuk dalam total pembayaran pada menu Tagihan.";
    }

    private function caraBayarQris(): string
    {
        return "Cara bayar QRIS:\n1. Buka menu Tagihan.\n2. Pilih tagihan yang ingin dibayar.\n3. Pilih QRIS lalu klik Lanjut ke Midtrans.\n4. Scan QR dari e-wallet atau mobile banking, lalu tunggu status terkonfirmasi.";
    }

    private function caraBayarVa(): string
    {
        return "Cara bayar Virtual Account:\n1. Buka menu Tagihan dan pilih tagihan.\n2. Pilih BCA VA atau Mandiri VA.\n3. Klik Lanjut ke Midtrans.\n4. Gunakan nomor VA yang dibuat Midtrans dan transfer sesuai nominal persis.";
    }

    private function statusTerakhir(Collection $invoices): string
    {
        $latestPaid = $invoices
            ->filter(fn (Invoice $invoice) => $invoice->status === 'lunas')
            ->sortByDesc(fn (Invoice $invoice) => $invoice->paid_date ?? $invoice->updated_at)
            ->first();

        if (!$latestPaid) {
            return 'Belum ada pembayaran lunas yang tercatat. Kalau baru membayar, tunggu konfirmasi Midtrans atau hubungi admin.';
        }

        $paidDate = $this->formatDate($latestPaid->paid_date ?: $latestPaid->updated_at);
        $method = strtoupper((string) ($latestPaid->payment_method ?: 'Midtrans'));

        return "Pembayaran terakhir: {$latestPaid->name} sebesar {$this->rupiah($this->invoiceTotal($latestPaid))}, metode {$method}, tanggal {$paidDate}.";
    }

    private function rekomendasi(Collection $activeInvoices): string
    {
        if ($activeInvoices->isEmpty()) {
            return 'Tidak ada tagihan aktif. Rekomendasi saya: simpan bukti pembayaran terakhir dan pantau notifikasi tagihan berikutnya.';
        }

        $priority = $activeInvoices->firstWhere('status', 'terlambat') ?? $activeInvoices->first();
        $dueDate = $this->formatDate($priority->due_date);

        return "Prioritaskan {$priority->name} sebesar {$this->rupiah($this->invoiceTotal($priority))}. Jatuh tempo {$dueDate}. Bayar dari menu Tagihan agar status otomatis mengikuti konfirmasi Midtrans.";
    }

    private function kontakAdmin(): string
    {
        return 'Untuk kendala akun, nominal tagihan, atau pembayaran yang belum terkonfirmasi, hubungi admin Pondok Pesantren Mahasiswa Jagad Alimussirry melalui bagian administrasi.';
    }

    private function invoiceTotal(Invoice $invoice): int
    {
        return (int) ($invoice->total ?: ((int) $invoice->amount + (int) $invoice->penalty));
    }

    private function rupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    private function formatDate(mixed $date): string
    {
        if (!$date) {
            return '-';
        }

        if ($date instanceof CarbonInterface) {
            return $date->translatedFormat('d F Y');
        }

        return \Illuminate\Support\Carbon::parse($date)->translatedFormat('d F Y');
    }
}
