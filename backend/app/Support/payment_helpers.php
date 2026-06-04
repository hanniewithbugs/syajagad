<?php

use Carbon\Carbon;
use Illuminate\Support\Collection;

if (! function_exists('getPaymentStatus')) {
    function getPaymentStatus(mixed $subject): array
    {
        if ($subject instanceof Collection) {
            if ($subject->isEmpty()) {
                return ['status' => 'no_invoice', 'label' => 'Belum Ada Tagihan'];
            }

            if ($subject->contains(fn ($invoice) => getPaymentStatus($invoice)['status'] === 'terlambat')) {
                return ['status' => 'terlambat', 'label' => 'Menunggak'];
            }

            if ($subject->contains(fn ($invoice) => getPaymentStatus($invoice)['status'] === 'cicilan')) {
                return ['status' => 'cicilan', 'label' => 'Cicilan'];
            }

            if ($subject->contains(fn ($invoice) => getPaymentStatus($invoice)['status'] === 'belum')) {
                return ['status' => 'belum', 'label' => 'Belum Bayar'];
            }

            return ['status' => 'lunas', 'label' => 'Lunas'];
        }

        $rawStatus = strtolower((string) ($subject->status ?? 'belum'));
        $amount = (int) ($subject->amount ?? 0);
        $currentPenalty = (int) ($subject->penalty ?? 0);
        $paidAmount = (int) ($subject->paid_amount ?? 0);
        $dueDate = null;

        if (! empty($subject->due_date)) {
            $dueDate = $subject->due_date instanceof Carbon
                ? $subject->due_date->copy()
                : Carbon::parse($subject->due_date);
        }

        $penalty = $currentPenalty;
        if ($rawStatus !== 'lunas' && $dueDate && now()->greaterThan($dueDate)) {
            $months = (int) $dueDate->diffInMonths(now());
            if (now()->day > $dueDate->day) {
                $months++;
            }
            $penalty = (int) max($penalty, max(1, $months) * 50000);
        }

        $total = (int) ($amount + $penalty);
        $status = match (true) {
            $rawStatus === 'lunas' => 'lunas',
            $rawStatus === 'cicilan' || ($paidAmount > 0 && $paidAmount < $total) => 'cicilan',
            $dueDate && now()->greaterThan($dueDate) => 'terlambat',
            default => 'belum',
        };

        return [
            'status' => $status,
            'label' => match ($status) {
                'lunas' => 'Lunas',
                'terlambat' => 'Menunggak',
                'cicilan' => 'Cicilan',
                default => 'Belum Bayar',
            },
            'penalty' => $penalty,
            'total' => $total,
        ];
    }
}

if (! function_exists('paymentMetricsForInvoice')) {
    function paymentMetricsForInvoice(mixed $invoice): array
    {
        $status = getPaymentStatus($invoice);
        $amount = (int) ($invoice->amount ?? 0);
        $penalty = (int) ($status['penalty'] ?? ($invoice->penalty ?? 0));
        $total = (int) ($status['total'] ?? ($amount + $penalty));
        $paidAmount = (int) ($invoice->paid_amount ?? 0);

        if ($status['status'] === 'lunas') {
            $paidAmount = $total;
        }

        $outstanding = max($total - $paidAmount, 0);

        return [
            'status' => $status['status'],
            'label' => $status['label'],
            'amount' => $amount,
            'penalty' => $penalty,
            'total' => $total,
            'paid' => $paidAmount,
            'outstanding' => $outstanding,
        ];
    }
}

if (! function_exists('summarizePaymentCollection')) {
    function summarizePaymentCollection(Collection $invoices): array
    {
        $distribution = [
            'lunas' => 0,
            'belum' => 0,
            'terlambat' => 0,
            'cicilan' => 0,
        ];

        $summary = [
            'total_invoices' => $invoices->count(),
            'total_tagihan' => 0,
            'total_pembayaran' => 0,
            'total_denda' => 0,
            'outstanding' => 0,
            'overdue_amount' => 0,
            'distribution' => $distribution,
        ];

        foreach ($invoices as $invoice) {
            $metrics = paymentMetricsForInvoice($invoice);
            $summary['total_tagihan'] += $metrics['total'];
            $summary['total_pembayaran'] += $metrics['paid'];
            $summary['total_denda'] += $metrics['penalty'];
            $summary['outstanding'] += $metrics['outstanding'];

            if (array_key_exists($metrics['status'], $summary['distribution'])) {
                $summary['distribution'][$metrics['status']]++;
            }

            if ($metrics['status'] === 'terlambat') {
                $summary['overdue_amount'] += $metrics['outstanding'];
            }
        }

        return $summary;
    }
}
