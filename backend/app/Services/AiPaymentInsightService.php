<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AiPaymentInsightService
{
    public function forSantri(User $user): array
    {
        $invoices = $user->invoices()->orderBy('due_date')->get();
        $summary = $this->buildSummary($invoices);
        $fallback = $this->fallbackInsight($summary);

        if (blank(config('services.openai.api_key'))) {
            return $fallback + [
                'source' => 'local',
                'model' => null,
            ];
        }

        try {
            $response = Http::withToken(config('services.openai.api_key'))
                ->timeout((int) config('services.openai.timeout', 12))
                ->post('https://api.openai.com/v1/responses', [
                    'model' => config('services.openai.model', 'gpt-5-mini'),
                    'input' => $this->prompt($summary),
                ]);

            if (! $response->successful()) {
                return $fallback + [
                    'source' => 'local',
                    'model' => null,
                    'error' => 'OpenAI belum bisa merespons.',
                ];
            }

            $text = $response->json('output_text')
                ?? data_get($response->json(), 'output.0.content.0.text')
                ?? '';

            $ai = $this->parseJson($text);

            return [
                'risk_score' => $this->clampScore($ai['risk_score'] ?? $fallback['risk_score']),
                'risk_label' => $this->normalizeLabel($ai['risk_label'] ?? $fallback['risk_label']),
                'reason' => Str::limit((string) ($ai['reason'] ?? $fallback['reason']), 180, ''),
                'recommendation' => Str::limit((string) ($ai['recommendation'] ?? $fallback['recommendation']), 220, ''),
                'next_action' => Str::limit((string) ($ai['next_action'] ?? $fallback['next_action']), 120, ''),
                'source' => 'openai',
                'model' => config('services.openai.model', 'gpt-5-mini'),
            ];
        } catch (\Throwable $exception) {
            return $fallback + [
                'source' => 'local',
                'model' => null,
                'error' => 'AI eksternal belum aktif, memakai analisis lokal.',
            ];
        }
    }

    private function buildSummary(Collection $invoices): array
    {
        $total = max($invoices->count(), 1);
        $paid = $invoices->where('status', 'lunas')->count();
        $overdue = $invoices->where('status', 'terlambat')->count();
        $unpaid = $invoices->whereIn('status', ['belum', 'terlambat'])->count();
        $outstanding = $invoices
            ->whereIn('status', ['belum', 'terlambat'])
            ->sum(fn (Invoice $invoice) => (int) $invoice->total);

        $oldestDueDate = $invoices
            ->whereIn('status', ['belum', 'terlambat'])
            ->sortBy('due_date')
            ->first()?->due_date;
        $oldestDueDate = $oldestDueDate ? Carbon::parse($oldestDueDate)->format('Y-m-d') : null;

        $paidRatio = $paid / $total;
        $score = min(100, max(0, (int) round(
            ($overdue * 30)
            + (($unpaid / $total) * 35)
            + (min($outstanding, 5000000) / 5000000 * 25)
            - ($paidRatio * 15)
        )));

        return [
            'total_invoices' => $invoices->count(),
            'paid_invoices' => $paid,
            'unpaid_invoices' => $unpaid,
            'overdue_invoices' => $overdue,
            'total_penalty' => (int) $invoices->sum('penalty'),
            'outstanding_total' => (int) $outstanding,
            'oldest_due_date' => $oldestDueDate,
            'local_score' => $score,
        ];
    }

    private function fallbackInsight(array $summary): array
    {
        $score = $summary['local_score'];
        $label = $this->normalizeLabel($score >= 75 ? 'Tinggi' : ($score >= 40 ? 'Sedang' : 'Rendah'));

        if ($summary['unpaid_invoices'] === 0) {
            return [
                'risk_score' => 8,
                'risk_label' => 'Rendah',
                'reason' => 'Semua tagihan sudah lunas dan tidak ada tunggakan aktif.',
                'recommendation' => 'Pertahankan pola pembayaran tepat waktu agar status administrasi tetap aman.',
                'next_action' => 'Pantau tagihan berikutnya.',
            ];
        }

        return [
            'risk_score' => $score,
            'risk_label' => $label,
            'reason' => "{$summary['unpaid_invoices']} tagihan belum lunas dengan total tertagih Rp " . number_format($summary['outstanding_total'], 0, ',', '.') . '.',
            'recommendation' => $summary['overdue_invoices'] > 0
                ? 'Prioritaskan tagihan terlambat terlebih dahulu agar denda tidak bertambah.'
                : 'Lunasi sebelum jatuh tempo untuk menjaga status administrasi tetap baik.',
            'next_action' => 'Buka menu Tagihan dan pilih metode pembayaran.',
        ];
    }

    private function prompt(array $summary): string
    {
        return 'Anda adalah analis pembayaran SPP pesantren. Buat insight singkat dalam bahasa Indonesia untuk santri berdasarkan ringkasan JSON berikut. Jangan menyebut data pribadi. Kembalikan JSON valid saja dengan keys: risk_score integer 0-100, risk_label salah satu Rendah/Sedang/Tinggi, reason maksimal 1 kalimat, recommendation maksimal 1 kalimat, next_action maksimal 1 frasa. Ringkasan: ' . json_encode($summary);
    }

    private function parseJson(string $text): array
    {
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function clampScore(mixed $score): int
    {
        return min(100, max(0, (int) $score));
    }

    private function normalizeLabel(string $label): string
    {
        return in_array($label, ['Rendah', 'Sedang', 'Tinggi'], true) ? $label : 'Sedang';
    }
}
