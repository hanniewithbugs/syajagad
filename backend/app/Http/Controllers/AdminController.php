<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

require_once __DIR__ . '/../../Support/payment_helpers.php';

class AdminController extends Controller
{
    private const PERMISSIONS = [
        'manage_santri' => 'Kelola Data Santri',
        'manage_invoices' => 'Kelola Tagihan',
        'manage_permissions' => 'Kelola Permission Admin',
        'view_audit_logs' => 'Lihat Audit Log',
    ];

    public function stats(): \Illuminate\Http\JsonResponse
    {
        $this->syncOverdueInvoices();

        $students = User::where('role', 'santri')->with('invoices')->get();
        $totalSantri = $students->count();
        $totalPaid = $students->filter(fn (User $santri) => getPaymentStatus($santri->invoices)['status'] === 'lunas')->count();
        $totalUnpaid = $students->filter(fn (User $santri) => in_array(getPaymentStatus($santri->invoices)['status'], ['belum', 'terlambat', 'cicilan'], true))->count();
        $totalTunggak = $students->filter(fn (User $santri) => getPaymentStatus($santri->invoices)['status'] === 'terlambat')->count();
        $totalRevenue = Invoice::where('status', 'lunas')->sum('total');
        $totalTagihan = Invoice::sum('total');
        $totalPenalty = Invoice::sum('penalty');
        $outstanding = Invoice::whereIn('status', ['belum', 'terlambat'])->sum('total');
        $overdueAmount = Invoice::where('status', 'terlambat')->sum('total');
        $semesterFee = 2200000;
        $collectibility = $totalTagihan > 0 ? round(($totalRevenue / $totalTagihan) * 100) : 0;
        $pendingVerification = Invoice::whereNotNull('midtrans_order_id')
            ->where('status', '!=', 'lunas')
            ->count();
        $pendingVerificationAmount = Invoice::whereNotNull('midtrans_order_id')
            ->where('status', '!=', 'lunas')
            ->sum('total');
        $riskRows = $students
            ->map(fn (User $santri) => [
                'id' => $santri->id,
                'name' => $santri->name,
                'nis' => $santri->nis,
                ...$this->buildPaymentRisk($santri->invoices),
            ])
            ->sortByDesc('risk_score')
            ->values();

        return response()->json([
            'totalSantri' => $totalSantri,
            'totalPaid' => $totalPaid,
            'totalUnpaid' => $totalUnpaid,
            'totalTunggak' => $totalTunggak,
            'totalCuti' => User::where('role', 'santri')->where('santri_status', 'cuti')->count(),
            'totalRevenue' => $totalRevenue,
            'totalTagihan' => $totalTagihan,
            'totalPenalty' => $totalPenalty,
            'overdueAmount' => $overdueAmount,
            'semesterFee' => $semesterFee,
            'collectibility' => $collectibility,
            'pendingVerification' => $pendingVerification,
            'pendingVerificationAmount' => $pendingVerificationAmount,
            'outstanding' => $outstanding,
            'highRiskSantri' => $riskRows->where('risk_label', 'Tinggi')->count(),
            'topRiskSantri' => $riskRows->take(5)->values(),
        ]);
    }

    public function listSantri(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->syncOverdueInvoices();

        $query = User::where('role', 'santri')
            ->with('invoices')
            ->withCount([
                'invoices as total_invoices',
                'invoices as paid_invoices' => function ($query) {
                    $query->where('status', 'lunas');
                },
                'invoices as unpaid_invoices' => function ($query) {
                    $query->whereIn('status', ['belum', 'terlambat']);
                },
                'invoices as overdue_invoices' => function ($query) {
                    $query->where('status', 'terlambat');
                },
            ]);

        if ($request->filled('search')) {
            $term = '%' . $request->string('search')->trim() . '%';
            $query->where(function ($sub) use ($term) {
                $sub->where('name', 'like', $term)
                    ->orWhere('nis', 'like', $term)
                    ->orWhere('gender', 'like', $term)
                    ->orWhere('username', 'like', $term)
                    ->orWhere('email', 'like', $term);
            });
        }

        $santri = $query->orderBy('name')->get([
            'id',
            'name',
            'nis',
            'gender',
            'username',
            'email',
            'santri_status',
            'tgl_lahir',
            'alamat',
            'created_at',
        ]);

        $rows = $santri->map(function ($item) {
            $paymentStatus = getPaymentStatus($item->invoices)['label'];
            $risk = $this->buildPaymentRisk($item->invoices);
            $santriStatus = $item->santri_status === 'cuti' ? 'Cuti' : 'Aktif';

            return [
                'id' => $item->id,
                'name' => $item->name,
                'nis' => $item->nis,
                'gender' => $item->gender ?? '-',
                'username' => $item->username,
                'email' => $item->email,
                'santri_status' => $item->santri_status ?? 'aktif',
                'tgl_lahir' => optional($item->tgl_lahir)->format('Y-m-d'),
                'alamat' => $item->alamat,
                'created_at' => $item->created_at->toDateTimeString(),
                'status' => $santriStatus,
                'payment_status' => $paymentStatus,
                'angkatan' => $item->created_at->format('Y'),
                'total_invoices' => $item->total_invoices,
                'paid_invoices' => $item->paid_invoices,
                'unpaid_invoices' => $item->unpaid_invoices,
                'overdue_invoices' => $item->overdue_invoices,
                'risk_score' => $risk['risk_score'],
                'risk_label' => $risk['risk_label'],
                'risk_reason' => $risk['risk_reason'],
            ];
        });

        return response()->json(['data' => $rows]);
    }

    public function storeSantri(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorizeAdminPermission('manage_santri');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['required', 'string', 'max:20', 'unique:users,nis'],
            'gender' => ['required', 'string', 'in:Laki-laki,Perempuan'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'tgl_lahir' => ['required', 'date'],
            'alamat' => ['required', 'string', 'max:255'],
            'santri_status' => ['nullable', 'string', 'in:aktif,cuti'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'nis' => $validated['nis'],
            'gender' => $validated['gender'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => 'santri',
            'santri_status' => $validated['santri_status'] ?? 'aktif',
            'tgl_lahir' => $validated['tgl_lahir'],
            'alamat' => $validated['alamat'],
        ]);

        $this->writeAuditLog('santri.created', "Menambahkan santri {$user->name}", $user, [
            'nis' => $user->nis,
            'email' => $user->email,
        ]);

        return response()->json(['data' => $user], 201);
    }

    public function showSantri(User $santri): \Illuminate\Http\JsonResponse
    {
        $this->syncOverdueInvoices();

        if ($santri->role !== 'santri') {
            return response()->json(['message' => 'Santri tidak ditemukan'], 404);
        }

        $santri->load('invoices');
        $risk = $this->buildPaymentRisk($santri->invoices);

        return response()->json([
            'data' => [
                'id' => $santri->id,
                'name' => $santri->name,
                'nis' => $santri->nis,
                'gender' => $santri->gender ?? '-',
                'username' => $santri->username,
                'email' => $santri->email,
                'santri_status' => $santri->santri_status ?? 'aktif',
                'tgl_lahir' => optional($santri->tgl_lahir)->format('Y-m-d'),
                'alamat' => $santri->alamat,
                'created_at' => $santri->created_at->toDateTimeString(),
                'status' => $santri->santri_status === 'cuti' ? 'Cuti' : 'Aktif',
                'payment_status' => $this->studentPaymentStatus($santri),
                'angkatan' => $santri->created_at->format('Y'),
                'risk_score' => $risk['risk_score'],
                'risk_label' => $risk['risk_label'],
                'risk_reason' => $risk['risk_reason'],
                'invoices' => $santri->invoices->map(function ($invoice) {
                    $status = getPaymentStatus($invoice);

                    return [
                        'id' => $invoice->id,
                        'name' => $invoice->name,
                        'status' => $status['status'],
                        'status_label' => $status['label'],
                        'due_date' => $this->formatInvoiceDate($invoice->due_date),
                        'amount' => $invoice->amount,
                        'penalty' => $status['penalty'],
                        'total' => $status['total'],
                        'updated_at' => optional($invoice->updated_at)->format('Y-m-d'),
                    ];
                }),
            ],
        ]);
    }

    public function updateSantri(Request $request, User $santri): \Illuminate\Http\JsonResponse
    {
        $this->authorizeAdminPermission('manage_santri');

        if ($santri->role !== 'santri') {
            return response()->json(['message' => 'Santri tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['required', 'string', 'max:20', 'unique:users,nis,' . $santri->id],
            'gender' => ['required', 'string', 'in:Laki-laki,Perempuan'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $santri->id],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,' . $santri->id],
            'password' => ['nullable', 'string', 'min:8'],
            'tgl_lahir' => ['required', 'date'],
            'alamat' => ['required', 'string', 'max:255'],
            'santri_status' => ['nullable', 'string', 'in:aktif,cuti'],
        ]);

        $santri->update(array_filter([
            'name' => $validated['name'],
            'nis' => $validated['nis'],
            'gender' => $validated['gender'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'santri_status' => $validated['santri_status'] ?? 'aktif',
            'tgl_lahir' => $validated['tgl_lahir'],
            'alamat' => $validated['alamat'],
            'password' => $validated['password'] ? Hash::make($validated['password']) : null,
        ]));

        $this->writeAuditLog('santri.updated', "Mengubah data santri {$santri->name}", $santri, [
            'nis' => $santri->nis,
        ]);

        return response()->json(['data' => $santri]);
    }

    public function storeInvoice(Request $request, User $santri): \Illuminate\Http\JsonResponse
    {
        $this->authorizeAdminPermission('manage_invoices');

        if ($santri->role !== 'santri') {
            return response()->json(['message' => 'Santri tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'due_date' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:1000'],
            'penalty' => ['nullable', 'integer', 'min:0'],
        ]);
        if ($error = $this->semesterDueDateError($validated['due_date'])) {
            return response()->json([
                'message' => $error,
                'errors' => ['due_date' => [$error]],
            ], 422);
        }

        $penalty = (int) ($validated['penalty'] ?? 0);
        $paymentStatus = getPaymentStatus((object) [
            'due_date' => $validated['due_date'],
            'amount' => $validated['amount'],
            'penalty' => $penalty,
        ]);
        $invoiceName = $this->semesterInvoiceName($validated['due_date']);
        $invoice = $santri->invoices()->create([
            'name' => $invoiceName,
            'description' => $validated['description'] ?? "Pembayaran {$invoiceName}",
            'due_date' => $validated['due_date'],
            'amount' => (int) $validated['amount'],
            'penalty' => $penalty,
            'total' => $paymentStatus['total'],
            'status' => $paymentStatus['status'],
        ]);

        $this->notifyUser(
            $santri,
            'invoice_created',
            'Tagihan baru tersedia',
            "Tagihan {$invoice->name} sebesar {$this->formatRupiah($invoice->total)} telah dibuat.",
            ['invoice_id' => $invoice->id]
        );
        $this->writeAuditLog('invoice.created', "Membuat tagihan {$invoice->name} untuk {$santri->name}", $invoice, [
            'santri_id' => $santri->id,
            'total' => $invoice->total,
        ]);

        return response()->json(['data' => $invoice], 201);
    }

    public function storeBulkInvoice(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorizeAdminPermission('manage_invoices');

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'due_date' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:1000'],
            'penalty' => ['nullable', 'integer', 'min:0'],
            'santri_ids' => ['nullable', 'array'],
            'santri_ids.*' => ['integer', 'exists:users,id'],
        ]);
        if ($error = $this->semesterDueDateError($validated['due_date'])) {
            return response()->json([
                'message' => $error,
                'errors' => ['due_date' => [$error]],
            ], 422);
        }

        $query = User::where('role', 'santri');
        if (! empty($validated['santri_ids'])) {
            $query->whereIn('id', $validated['santri_ids']);
        }

        $penalty = (int) ($validated['penalty'] ?? 0);
        $invoiceName = $this->semesterInvoiceName($validated['due_date']);
        $created = 0;
        $skipped = 0;

        $query->get()->each(function (User $santri) use ($validated, $penalty, $invoiceName, &$created, &$skipped) {
            $exists = $santri->invoices()
                ->where('name', $invoiceName)
                ->whereDate('due_date', $validated['due_date'])
                ->exists();

            if ($exists) {
                $skipped++;
                return;
            }

            $paymentStatus = getPaymentStatus((object) [
                'due_date' => $validated['due_date'],
                'amount' => $validated['amount'],
                'penalty' => $penalty,
            ]);

            $santri->invoices()->create([
                'user_id' => $santri->id,
                'name' => $invoiceName,
                'description' => $validated['description'] ?? "Pembayaran {$invoiceName}",
                'due_date' => $validated['due_date'],
                'amount' => (int) $validated['amount'],
                'penalty' => $penalty,
                'total' => $paymentStatus['total'],
                'status' => $paymentStatus['status'],
            ]);

            $this->notifyUser(
                $santri,
                'invoice_created',
                'Tagihan baru tersedia',
                "Tagihan {$invoiceName} sebesar {$this->formatRupiah((int) $validated['amount'] + $penalty)} telah dibuat.",
                ['invoice_name' => $invoiceName]
            );

            $created++;
        });

        $this->writeAuditLog('invoice.bulk_created', "Membuat tagihan massal {$invoiceName}", null, [
            'created' => $created,
            'skipped' => $skipped,
            'due_date' => $validated['due_date'],
        ]);

        return response()->json([
            'message' => "Tagihan massal selesai. {$created} dibuat, {$skipped} dilewati.",
            'created' => $created,
            'skipped' => $skipped,
        ], 201);
    }

    public function deleteSantri(User $santri): \Illuminate\Http\JsonResponse
    {
        $this->authorizeAdminPermission('manage_santri');

        if ($santri->role !== 'santri') {
            return response()->json(['message' => 'Santri tidak ditemukan'], 404);
        }

        $santri->invoices()->delete();
        $this->writeAuditLog('santri.deleted', "Menghapus santri {$santri->name}", $santri, [
            'nis' => $santri->nis,
        ]);
        $santri->delete();

        return response()->json(['message' => 'Santri berhasil dihapus']);
    }

    public function listPayments(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->syncOverdueInvoices();

        $santri = User::where('role', 'santri')
            ->with(['invoices' => fn ($query) => $query->orderByDesc('due_date')])
            ->orderBy('name')
            ->get();

        $rows = $santri->map(function (User $student) {
            if ($student->invoices->isEmpty()) {
                return [
                    'id' => 'student-' . $student->id,
                    'student_id' => $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis ?? '-',
                    'gender' => $student->gender ?? '-',
                    'angkatan' => optional($student->created_at)->format('Y'),
                    'santri_status' => $student->santri_status ?? 'aktif',
                    'student_status_label' => $student->santri_status === 'cuti' ? 'Cuti' : 'Aktif',
                    'payment_status' => 'Belum Ada Tagihan',
                    'month' => 'Belum ada tagihan semester',
                    'status' => 'no_invoice',
                    'status_label' => 'Belum Ada Tagihan',
                    'payment_date' => '-',
                    'total' => 0,
                    'penalty' => 0,
                ];
            }

            $invoice = $student->invoices
                ->sortBy(fn (Invoice $invoice) => match (getPaymentStatus($invoice)['status']) {
                    'terlambat' => 0,
                    'cicilan' => 1,
                    'belum' => 2,
                    'lunas' => 3,
                    default => 3,
                })
                ->first();
            $status = getPaymentStatus($invoice);

            return [
                'id' => $invoice->id,
                'student_id' => $student->id,
                'name' => $student->name,
                'nis' => $student->nis ?? '-',
                'gender' => $student->gender ?? '-',
                'angkatan' => optional($student->created_at)->format('Y'),
                'santri_status' => $student->santri_status ?? 'aktif',
                'student_status_label' => $student->santri_status === 'cuti' ? 'Cuti' : 'Aktif',
                'payment_status' => $this->studentPaymentStatus($student),
                'month' => $invoice->name,
                'status' => $status['status'],
                'status_label' => $status['label'],
                'payment_date' => optional($invoice->paid_date ?? $invoice->updated_at)->format('Y-m-d') ?? '-',
                'total' => $invoice->total,
                'penalty' => $invoice->penalty,
            ];
        })->values();

        return response()->json(['data' => $rows]);
    }

    public function exportReport(Request $request, string $format)
    {
        abort_unless(in_array($format, ['csv', 'excel', 'pdf'], true), 404);

        $rows = $this->paymentReportRows($request);
        $filename = 'laporan-syajagad-' . now()->format('Ymd-His');

        if ($format === 'pdf') {
            return response($this->printableReportHtml($rows))
                ->header('Content-Type', 'text/html; charset=UTF-8')
                ->header('Content-Disposition', "inline; filename=\"{$filename}.html\"");
        }

        if ($format === 'excel') {
            return response($this->excelReportHtml($rows))
                ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
                ->header('Content-Disposition', "attachment; filename=\"{$filename}.xls\"");
        }

        return response($this->tableExportContent($rows, ','))
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}.csv\"");
    }

    public function reportData(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json(['data' => $this->paymentReportRows($request)]);
    }

    public function changePassword(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'old_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (! $user || ! Hash::check($validated['old_password'], $user->password)) {
            return response()->json([
                'message' => 'Kata sandi lama tidak sesuai.',
                'errors' => [
                    'old_password' => ['Kata sandi lama tidak sesuai.'],
                ],
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        $this->writeAuditLog('admin.password_changed', 'Mengubah kata sandi akun sendiri', $user);

        return response()->json(['message' => 'Kata sandi berhasil diubah.']);
    }

    public function listAuditLogs(): \Illuminate\Http\JsonResponse
    {
        $this->authorizeAdminPermission('view_audit_logs');

        $logs = AuditLog::with('actor')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'actor' => $log->actor?->name ?? 'Sistem',
                'action' => $log->action,
                'description' => $log->description,
                'metadata' => $log->metadata ?? [],
                'created_at' => $log->created_at->format('Y-m-d H:i'),
            ]);

        return response()->json(['data' => $logs]);
    }

    public function listPermissions(): \Illuminate\Http\JsonResponse
    {
        $this->authorizeAdminPermission('manage_permissions');

        $admins = User::where('role', 'admin')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'username', 'admin_permissions'])
            ->map(fn (User $admin) => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'username' => $admin->username,
                'permissions' => $admin->admin_permissions ?? array_keys(self::PERMISSIONS),
                'full_access' => $admin->admin_permissions === null,
            ]);

        return response()->json([
            'permissions' => self::PERMISSIONS,
            'admins' => $admins,
        ]);
    }

    public function updatePermissions(Request $request, User $admin): \Illuminate\Http\JsonResponse
    {
        $this->authorizeAdminPermission('manage_permissions');

        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Admin tidak ditemukan'], 404);
        }

        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', array_keys(self::PERMISSIONS))],
        ]);

        $admin->update([
            'admin_permissions' => array_values(array_unique($validated['permissions'])),
        ]);

        $this->writeAuditLog('admin.permissions_updated', "Mengubah permission admin {$admin->name}", $admin, [
            'permissions' => $admin->admin_permissions,
        ]);

        return response()->json([
            'message' => 'Permission admin berhasil diperbarui.',
            'data' => [
                'id' => $admin->id,
                'permissions' => $admin->admin_permissions,
            ],
        ]);
    }

    private function buildPaymentRisk(Collection $invoices): array
    {
        $total = max($invoices->count(), 1);
        $overdue = $invoices->where('status', 'terlambat')->count();
        $unpaid = $invoices->whereIn('status', ['belum', 'terlambat'])->count();
        $paid = $invoices->where('status', 'lunas')->count();
        $maxDaysOverdue = $invoices
            ->filter(function (Invoice $invoice) {
                $dueDate = $this->parseInvoiceDate($invoice->due_date);

                return $invoice->status !== 'lunas' && $dueDate && $dueDate->isPast();
            })
            ->map(function (Invoice $invoice) {
                $dueDate = $this->parseInvoiceDate($invoice->due_date);

                return $dueDate ? $dueDate->diffInDays(now()) : 0;
            })
            ->max() ?? 0;
        $penalty = $invoices->sum('penalty');

        $score = min(100, (int) round(
            ($overdue * 30)
            + (($unpaid / $total) * 30)
            + (min($maxDaysOverdue, 60) * 0.45)
            + (min($penalty, 500000) / 500000 * 13)
            - (($paid / $total) * 10)
        ));
        $score = max(0, $score);

        $label = $score >= 70 ? 'Tinggi' : ($score >= 35 ? 'Sedang' : 'Rendah');
        $reason = $overdue > 0
            ? "{$overdue} tagihan menunggak, {$unpaid} belum lunas"
            : ($unpaid > 0 ? "{$unpaid} tagihan belum lunas" : 'Pembayaran tertib');

        return [
            'risk_score' => $score,
            'risk_label' => $label,
            'risk_reason' => $reason,
        ];
    }

    private function syncOverdueInvoices(): void
    {
        Invoice::where('status', '!=', 'lunas')
            ->get()
            ->each(function (Invoice $invoice) {
                $status = getPaymentStatus($invoice);

                $invoice->update([
                    'status' => $status['status'],
                    'penalty' => $status['penalty'],
                    'total' => $status['total'],
                ]);
            });
    }

    private function calculateLateMonths(Carbon $dueDate): int
    {
        if (now()->lessThanOrEqualTo($dueDate)) {
            return 0;
        }

        $months = $dueDate->diffInMonths(now());

        if (now()->day > $dueDate->day) {
            $months++;
        }

        return max(1, $months);
    }

    private function studentPaymentStatus(User $santri): string
    {
        if (! $santri->relationLoaded('invoices')) {
            $santri->load('invoices');
        }

        return getPaymentStatus($santri->invoices)['label'];
    }

    private function invoiceStatusLabel(string $status): string
    {
        return match ($status) {
            'lunas' => 'Lunas',
            'terlambat' => 'Menunggak',
            'cicilan' => 'Cicilan',
            'no_invoice' => 'Belum Ada Tagihan',
            default => 'Belum Bayar',
        };
    }

    private function semesterDueDateError(string $date): ?string
    {
        $dueDate = $this->parseInvoiceDate($date);
        if (! $dueDate) {
            return 'Tanggal jatuh tempo tidak valid.';
        }

        return in_array((int) $dueDate->month, [1, 7], true)
            ? null
            : 'Pembayaran SPP semesteran hanya dimulai pada bulan Januari atau Juli.';
    }

    private function semesterInvoiceName(string $date): string
    {
        $dueDate = $this->parseInvoiceDate($date);
        $semester = (int) $dueDate->month === 7 ? 'Ganjil' : 'Genap';

        return "SPP Semester {$semester} {$dueDate->year}";
    }

    private function parseInvoiceDate(mixed $date): ?Carbon
    {
        if (! $date) {
            return null;
        }

        return $date instanceof Carbon ? $date : Carbon::parse($date);
    }

    private function formatInvoiceDate(mixed $date): ?string
    {
        return $this->parseInvoiceDate($date)?->format('Y-m-d');
    }

    private function authorizeAdminPermission(string $permission): void
    {
        if (! Auth::user()?->hasAdminPermission($permission)) {
            abort(403, 'Akun admin ini tidak memiliki izin untuk aksi tersebut.');
        }
    }

    private function writeAuditLog(string $action, string $description, ?object $target = null, array $metadata = []): void
    {
        AuditLog::create([
            'actor_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'target_type' => $target ? $target::class : null,
            'target_id' => $target->id ?? null,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
        ]);
    }

    private function notifyUser(User $user, string $type, string $title, string $message, array $metadata = []): void
    {
        UserNotification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }

    private function formatRupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    private function paymentReportRows(Request $request): array
    {
        $this->syncOverdueInvoices();

        $query = Invoice::with('user')->orderBy('user_id')->orderByDesc('due_date');

        if ($request->filled('angkatan')) {
            $query->whereHas('user', fn ($userQuery) => $userQuery->whereYear('created_at', (string) $request->string('angkatan')));
        }

        if ($request->filled('metode')) {
            $query->where('payment_method', (string) $request->string('metode'));
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('due_date', '>=', $request->date('start_date')->format('Y-m-d'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('due_date', '<=', $request->date('end_date')->format('Y-m-d'));
        }

        return $query->get()
            ->map(function (Invoice $invoice) {
                $status = getPaymentStatus($invoice);

                return [
                'Nama Santri' => $invoice->user?->name ?? '-',
                'NIS/NIP' => $invoice->user?->nis ?? '-',
                'Kelamin' => $invoice->user?->gender ?? '-',
                'Angkatan' => optional($invoice->user?->created_at)->format('Y') ?? '-',
                'Tagihan' => $invoice->name,
                'Pokok' => $invoice->amount,
                'Denda' => $status['penalty'],
                'Total' => $status['total'],
                'Status' => $status['label'],
                'Metode' => $invoice->payment_method ?? '-',
                'Tanggal Bayar' => $this->formatInvoiceDate($invoice->paid_date) ?? '-',
                'Update Terakhir' => optional($invoice->updated_at)->format('Y-m-d H:i'),
            ];
            })
            ->values()
            ->all();
    }

    private function tableExportContent(array $rows, string $separator): string
    {
        $headers = ['Nama Santri', 'NIS/NIP', 'Kelamin', 'Angkatan', 'Tagihan', 'Pokok', 'Denda', 'Total', 'Status', 'Metode', 'Tanggal Bayar', 'Update Terakhir'];
        $lines = [$this->joinExportRow($headers, $separator)];

        foreach ($rows as $row) {
            $lines[] = $this->joinExportRow(array_map(fn ($header) => $row[$header] ?? '', $headers), $separator);
        }

        return "\xEF\xBB\xBF" . implode("\n", $lines);
    }

    private function joinExportRow(array $values, string $separator): string
    {
        return implode($separator, array_map(function ($value) use ($separator) {
            $value = (string) $value;

            if ($separator === ',') {
                return '"' . str_replace('"', '""', $value) . '"';
            }

            return str_replace(["\t", "\n", "\r"], ' ', $value);
        }, $values));
    }

    private function excelReportHtml(array $rows): string
    {
        $headers = ['Nama Santri', 'NIS/NIP', 'Kelamin', 'Angkatan', 'Tagihan', 'Pokok', 'Denda', 'Total', 'Status', 'Metode', 'Tanggal Bayar', 'Update Terakhir'];
        $headerCells = collect($headers)->map(fn ($header) => '<th>' . e($header) . '</th>')->implode('');
        $bodyRows = collect($rows)->map(function (array $row) use ($headers) {
            $cells = collect($headers)->map(function ($header) use ($row) {
                $value = in_array($header, ['Pokok', 'Denda', 'Total'], true)
                    ? $this->formatRupiah((int) ($row[$header] ?? 0))
                    : ($row[$header] ?? '');

                return '<td>' . e((string) $value) . '</td>';
            })->implode('');

            return "<tr>{$cells}</tr>";
        })->implode('');

        return "\xEF\xBB\xBF" . <<<HTML
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th { background: #2f6b35; color: #ffffff; font-weight: 700; }
        th, td { border: 1px solid #b7d7bf; padding: 8px; white-space: nowrap; }
        td:nth-child(6), td:nth-child(7), td:nth-child(8) { mso-number-format:"\@"; }
    </style>
</head>
<body>
    <table>
        <thead><tr>{$headerCells}</tr></thead>
        <tbody>{$bodyRows}</tbody>
    </table>
</body>
</html>
HTML;
    }

    private function printableReportHtml(array $rows): string
    {
        $revenue = collect($rows)->where('Status', 'Lunas')->sum('Total');
        $outstanding = collect($rows)->whereIn('Status', ['Belum Bayar', 'Menunggak', 'Cicilan'])->sum('Total');
        $generatedAt = now()->format('d/m/Y H:i');
        $rowCount = count($rows);
        $studentBlocks = collect($rows)
            ->groupBy('NIS/NIP')
            ->map(function ($studentRows) {
                $first = $studentRows->first();
                $bodyRows = $studentRows->map(function (array $row) {
                    $cells = collect(['Tagihan', 'Pokok', 'Denda', 'Total', 'Status', 'Metode', 'Tanggal Bayar'])
                        ->map(function ($header) use ($row) {
                            $value = in_array($header, ['Pokok', 'Denda', 'Total'], true)
                                ? $this->formatRupiah((int) ($row[$header] ?? 0))
                                : ($row[$header] ?? '');

                            return '<td>' . e((string) $value) . '</td>';
                        })
                        ->implode('');

                    return "<tr>{$cells}</tr>";
                })->implode('');

                return <<<HTML
    <section class="student-block">
        <div class="student-head">
            <strong>{$this->escapeHtml($first['Nama Santri'])}</strong>
            <span>NIS {$this->escapeHtml($first['NIS/NIP'])} | {$this->escapeHtml($first['Kelamin'])} | Angkatan {$this->escapeHtml($first['Angkatan'])}</span>
        </div>
        <table>
            <thead>
                <tr><th>Tagihan</th><th>Pokok</th><th>Denda</th><th>Total</th><th>Status</th><th>Metode</th><th>Tanggal Bayar</th></tr>
            </thead>
            <tbody>{$bodyRows}</tbody>
        </table>
    </section>
HTML;
            })->implode('');

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan SyaJagad</title>
    <style>
        @page { size: A4 portrait; margin: 14mm; }
        body { font-family: Arial, sans-serif; color: #1f2937; margin: 0; }
        h1 { color: #2f6b35; margin: 0 0 4px; }
        .meta { color: #64748b; margin-bottom: 20px; }
        .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
        .box { border: 1px solid #b7d7bf; border-radius: 6px; padding: 10px 14px; background: #f3fbf4; }
        .box strong { display: block; font-size: 18px; margin-top: 4px; }
        .student-block { page-break-inside: avoid; break-inside: avoid; margin: 0 0 16px; border: 1px solid #b7d7bf; border-radius: 6px; overflow: hidden; }
        .student-head { background: #2f6b35; color: #fff; padding: 10px 12px; display: flex; justify-content: space-between; gap: 12px; }
        .student-head span { font-size: 12px; }
        table { border-collapse: collapse; width: 100%; font-size: 12px; }
        th, td { border: 1px solid #dbe3ef; padding: 7px; text-align: left; }
        th { background: #e8f5ea; color: #214d27; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Cetak / Simpan PDF</button>
    <h1>Laporan Pembayaran SyaJagad</h1>
    <div class="meta">Dibuat pada {$generatedAt}</div>
    <div class="summary">
        <div class="box">Pendapatan Lunas<strong>{$this->formatRupiah((int) $revenue)}</strong></div>
        <div class="box">Outstanding<strong>{$this->formatRupiah((int) $outstanding)}</strong></div>
        <div class="box">Jumlah Baris<strong>{$rowCount}</strong></div>
    </div>
    {$studentBlocks}
</body>
</html>
HTML;
    }

    private function escapeHtml(mixed $value): string
    {
        return e((string) $value);
    }
}
