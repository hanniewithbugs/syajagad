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
            $months = $dueDate->diffInMonths(now());
            if (now()->day > $dueDate->day) {
                $months++;
            }
            $penalty = max($penalty, max(1, $months) * 50000);
        }

        $total = $amount + $penalty;
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
