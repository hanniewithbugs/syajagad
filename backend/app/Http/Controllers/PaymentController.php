<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\User;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

require_once __DIR__ . '/../../Support/payment_helpers.php';

class PaymentController extends Controller
{
    public static function buildPaymentData(User $user): array
    {
        $invoices = Invoice::where('user_id', $user->id)
            ->orderBy('due_date')
            ->get();

        if ($invoices->isEmpty()) {
            return [
                'invoices' => [],
                'history' => [],
                'message' => 'Belum ada tagihan untuk akun ini.',
            ];
        }

        $activeInvoices = [];
        $historyInvoices = [];

        foreach ($invoices as $invoice) {
            $dueDate = Carbon::parse($invoice->due_date);
            $paymentStatus = getPaymentStatus($invoice);
            $status = $paymentStatus['status'];
            $penalty = $paymentStatus['penalty'];
            $total = $paymentStatus['total'];

            if ($invoice->status !== $status || (int) $invoice->penalty !== (int) $penalty || (int) $invoice->total !== (int) $total) {
                $invoice->update([
                    'status' => $status,
                    'penalty' => $penalty,
                    'total' => $total,
                ]);
            }

            $invoiceData = [
                'id' => $invoice->id,
                'name' => $invoice->name,
                'description' => $invoice->description,
                'due_date' => $dueDate->format('Y-m-d'),
                'dueDate' => $dueDate->format('Y-m-d'),
                'amount' => $invoice->amount,
                'penalty' => $penalty,
                'total' => $total,
                'status' => $status,
                'status_label' => $paymentStatus['label'],
                'paid_date' => self::formatDate($invoice->paid_date),
                'paidDate' => self::formatDate($invoice->paid_date),
                'method' => $invoice->payment_method,
                'transaction_id' => $invoice->midtrans_transaction_id,
                'order_id' => $invoice->midtrans_order_id,
            ];

            if ($status === 'lunas') {
                $historyInvoices[] = $invoiceData;
            } else {
                $activeInvoices[] = $invoiceData;
            }
        }

        return [
            'invoices' => $activeInvoices,
            'history' => $historyInvoices,
            'message' => count($activeInvoices) ? '' : 'Semua tagihan sudah lunas.',
        ];
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'invoice_id' => ['required', 'integer'],
            'payment_method' => ['required', 'in:qris,bca,mandiri'],
        ]);

        $user = Auth::user();
        $invoice = Invoice::where('user_id', $user->id)
            ->where('id', $request->integer('invoice_id'))
            ->first();

        if (! $invoice) {
            return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
        }

        if ($invoice->status === 'lunas') {
            return response()->json(['message' => 'Invoice sudah dibayar'], 400);
        }

        $serverKey = config('services.midtrans.server_key');
        $baseUrl = self::midtransBaseUrl();

        $orderId = 'SPP-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
        $total = $this->currentTotal($invoice);

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $total,
            ],
            'item_details' => [
                [
                    'id' => 'SPP-' . $invoice->id,
                    'price' => $total,
                    'quantity' => 1,
                    'name' => $invoice->name,
                ],
            ],
            'customer_details' => [
                'first_name' => $user->name,
                'email' => $user->email,
            ],
            'enabled_payments' => $this->enabledPayments($request->payment_method),
        ];

        $invoice->update([
            'midtrans_order_id' => $orderId,
            'payment_method' => $request->payment_method,
            'penalty' => max($invoice->penalty, $total - $invoice->amount),
            'total' => $total,
        ]);

        if (blank($serverKey)) {
            return response()->json([
                'message' => 'Midtrans belum dikonfigurasi. Isi MIDTRANS_SERVER_KEY dan MIDTRANS_CLIENT_KEY di file .env.',
            ], 422);
        }

        $response = Http::withBasicAuth($serverKey, '')
            ->post($baseUrl . '/snap/v1/transactions', $payload);

        if (! $response->successful()) {
            return response()->json([
                'message' => 'Gagal membuat transaksi Midtrans',
                'errors' => $response->json(),
            ], $response->status() >= 400 && $response->status() < 500 ? 422 : 500);
        }

        return response()->json([
            'snap_token' => $response->json('token'),
            'redirect_url' => $response->json('redirect_url'),
            'order_id' => $orderId,
            'gross_amount' => $total,
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'invoice_id' => ['required', 'integer'],
            'order_id' => ['required', 'string'],
            'transaction_id' => ['nullable', 'string'],
            'payment_method' => ['required', 'in:qris,bca,mandiri'],
            'status' => ['required', 'in:success,pending,error'],
        ]);

        $user = Auth::user();
        $invoice = Invoice::where('user_id', $user->id)
            ->where('id', $request->integer('invoice_id'))
            ->first();

        if (! $invoice) {
            return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
        }

        if ($invoice->midtrans_order_id !== $request->order_id) {
            return response()->json(['message' => 'Order ID tidak sesuai dengan invoice.'], 422);
        }

        if ($request->status === 'success' && blank(config('services.midtrans.server_key'))) {
            return response()->json(['message' => 'Midtrans belum dikonfigurasi.'], 422);
        }

        $sync = $this->syncInvoiceWithMidtrans($invoice);

        return response()->json([
            'success' => $sync['paid'],
            'pending' => $sync['pending'],
            'message' => $sync['message'],
            'invoice_status' => $invoice->fresh()->status,
        ]);
    }

    public function status(int $invoice)
    {
        $invoice = $this->findUserInvoice($invoice);

        if (! $invoice) {
            return response()->json([
                'paid' => false,
                'pending' => false,
                'message' => 'Tagihan tidak ditemukan atau sudah tidak tersedia.',
            ], 404);
        }

        if (! $invoice->midtrans_order_id) {
            return response()->json([
                'paid' => $invoice->status === 'lunas',
                'pending' => false,
                'message' => 'Belum ada transaksi Midtrans untuk invoice ini.',
            ]);
        }

        $sync = $this->syncInvoiceWithMidtrans($invoice);

        return response()->json($sync + [
            'invoice_status' => $invoice->fresh()->status,
        ]);
    }

    public function detail(int $invoice)
    {
        $invoice = $this->findUserInvoice($invoice);

        if (! $invoice) {
            return response()->json([
                'message' => 'Detail pembayaran tidak ditemukan atau sudah tidak tersedia.',
            ], 404);
        }

        $invoice->load('user');
        $status = getPaymentStatus($invoice);
        $metrics = paymentMetricsForInvoice($invoice);

        return response()->json([
            'data' => [
                'id' => $invoice->id,
                'name' => $invoice->name,
                'description' => $invoice->description,
                'status' => $status['status'],
                'status_label' => $status['label'],
                'amount' => $metrics['amount'],
                'penalty' => $metrics['penalty'],
                'total' => $metrics['total'],
                'paid_amount' => $metrics['paid'],
                'outstanding' => $metrics['outstanding'],
                'due_date' => self::formatDate($invoice->due_date),
                'paid_date' => self::formatDate($invoice->paid_date),
                'method' => $invoice->payment_method,
                'order_id' => $invoice->midtrans_order_id,
                'transaction_id' => $invoice->midtrans_transaction_id,
                'updated_at' => optional($invoice->updated_at)->format('Y-m-d H:i'),
                'student' => [
                    'name' => $invoice->user?->name,
                    'nis' => $invoice->user?->nis,
                    'email' => $invoice->user?->email,
                    'gender' => $invoice->user?->gender,
                    'alamat' => $invoice->user?->alamat,
                ],
                'proof' => $this->buildProofData($invoice),
            ],
        ]);
    }

    public function notification(Request $request)
    {
        $payload = $request->all();
        $serverKey = config('services.midtrans.server_key');

        if (blank($serverKey) || ! $this->isValidNotificationSignature($payload, $serverKey)) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        $invoice = Invoice::where('midtrans_order_id', $payload['order_id'] ?? null)->first();

        if (! $invoice) {
            return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
        }

        $this->applyMidtransStatus($invoice, $payload);

        return response()->json(['success' => true]);
    }

    private static function calculateLateMonths(Carbon $dueDate): int
    {
        $now = now();

        if ($now->lessThanOrEqualTo($dueDate)) {
            return 0;
        }

        $months = $dueDate->diffInMonths($now);

        if ($now->day > $dueDate->day) {
            $months++;
        }

        return max(0, $months);
    }

    private static function formatDate(mixed $date): ?string
    {
        if (blank($date)) {
            return null;
        }

        return $date instanceof Carbon
            ? $date->format('Y-m-d')
            : Carbon::parse($date)->format('Y-m-d');
    }

    private function enabledPayments(string $method): array
    {
        return match ($method) {
            'qris' => ['gopay', 'shopeepay'],
            'bca' => ['bca_va'],
            'mandiri' => ['echannel'],
            default => ['gopay', 'shopeepay', 'bca_va', 'echannel'],
        };
    }

    private function currentTotal(Invoice $invoice): int
    {
        return (int) getPaymentStatus($invoice)['total'];
    }

    private static function midtransBaseUrl(): string
    {
        return config('services.midtrans.is_production')
            ? 'https://api.midtrans.com'
            : 'https://api.sandbox.midtrans.com';
    }

    private function syncInvoiceWithMidtrans(Invoice $invoice): array
    {
        $serverKey = config('services.midtrans.server_key');

        if (blank($serverKey)) {
            return [
                'paid' => false,
                'pending' => false,
                'message' => 'Midtrans belum dikonfigurasi.',
            ];
        }

        $response = Http::withBasicAuth($serverKey, '')
            ->get(self::midtransBaseUrl() . '/v2/' . $invoice->midtrans_order_id . '/status');

        if (! $response->successful()) {
            return [
                'paid' => false,
                'pending' => false,
                'message' => 'Status pembayaran belum tersedia dari Midtrans.',
                'errors' => $response->json(),
            ];
        }

        return $this->applyMidtransStatus($invoice, $response->json());
    }

    private function applyMidtransStatus(Invoice $invoice, array $payload): array
    {
        $wasPaid = $invoice->status === 'lunas';
        $transactionStatus = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;
        $isPaid = $transactionStatus === 'settlement'
            || ($transactionStatus === 'capture' && in_array($fraudStatus, [null, 'accept'], true));
        $isPending = $transactionStatus === 'pending';

        $invoice->update([
            'midtrans_transaction_id' => $payload['transaction_id'] ?? $invoice->midtrans_transaction_id,
            'payment_method' => $this->normalizePaymentMethod($payload['payment_type'] ?? $invoice->payment_method),
            'midtrans_response' => $payload,
        ]);

        if ($isPaid) {
            $invoice->update([
                'status' => 'lunas',
                'paid_date' => now()->format('Y-m-d'),
            ]);

            if (! $wasPaid) {
                UserNotification::create([
                    'user_id' => $invoice->user_id,
                    'type' => 'payment_paid',
                    'title' => 'Pembayaran berhasil',
                    'message' => "Tagihan {$invoice->name} telah lunas. Terima kasih.",
                    'metadata' => ['invoice_id' => $invoice->id],
                ]);
            }
        }

        return [
            'paid' => $isPaid,
            'pending' => $isPending,
            'message' => $isPaid
                ? 'Pembayaran berhasil dan invoice sudah dilunasi.'
                : ($isPending ? 'Pembayaran masih menunggu penyelesaian di Midtrans.' : 'Pembayaran belum berhasil.'),
            'transaction_status' => $transactionStatus,
        ];
    }

    private function normalizePaymentMethod(?string $paymentType): ?string
    {
        return match ($paymentType) {
            'bank_transfer', 'bca_va' => 'bca',
            'echannel' => 'mandiri',
            'gopay', 'qris', 'shopeepay' => 'qris',
            default => $paymentType,
        };
    }

    private function findUserInvoice(int $invoiceId): ?Invoice
    {
        if ($invoiceId < 1) {
            return null;
        }

        return Invoice::where('id', $invoiceId)
            ->where('user_id', Auth::id())
            ->first();
    }

    private function buildProofData(Invoice $invoice): array
    {
        $payload = is_array($invoice->midtrans_response) ? $invoice->midtrans_response : [];
        $url = $this->findProofImageUrl($payload);

        return [
            'available' => filled($url),
            'label' => filled($url)
                ? 'Bukti pembayaran tersedia'
                : 'Bukti pembayaran belum tersedia.',
            'url' => $url,
            'filename' => 'bukti-pembayaran-' . $invoice->id . ($url ? '.' . pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION) : ''),
            'is_image' => filled($url),
        ];
    }

    private function findProofImageUrl(array $payload): ?string
    {
        $keys = ['proof_url', 'payment_proof_url', 'receipt_url', 'image_url', 'url'];

        foreach ($keys as $key) {
            $value = $payload[$key] ?? null;
            if (is_string($value) && $this->isSupportedProofImage($value)) {
                return $value;
            }
        }

        foreach ($payload as $value) {
            if (is_array($value)) {
                $nested = $this->findProofImageUrl($value);
                if ($nested) {
                    return $nested;
                }
            }
        }

        return null;
    }

    private function isSupportedProofImage(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?: $url;
        return (bool) preg_match('/\.(jpe?g|png|webp)$/i', $path);
    }

    private function isValidNotificationSignature(array $payload, string $serverKey): bool
    {
        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key'] as $key) {
            if (! isset($payload[$key])) {
                return false;
            }
        }

        $signature = hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . $serverKey
        );

        return hash_equals($signature, $payload['signature_key']);
    }
}
