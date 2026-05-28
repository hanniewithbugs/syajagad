<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_snap_transaction_with_selected_channel(): void
    {
        config([
            'services.midtrans.server_key' => 'SB-Mid-server-test',
            'services.midtrans.is_production' => false,
        ]);

        Http::fake([
            'api.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-token',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token',
            ], 201),
        ]);

        $user = User::factory()->create([
            'nis' => 'S007',
            'role' => 'santri',
        ]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'name' => 'SPP Semester Ganjil 2026',
            'description' => 'Pembayaran SPP semester ganjil 2026',
            'due_date' => '2026-07-15',
            'amount' => 2200000,
            'penalty' => 0,
            'total' => 2200000,
            'status' => 'belum',
        ]);

        $response = $this->actingAs($user)->postJson('/payment/checkout', [
            'invoice_id' => $invoice->id,
            'payment_method' => 'mandiri',
        ]);

        $response->assertOk()
            ->assertJsonPath('snap_token', 'snap-token');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.sandbox.midtrans.com/snap/v1/transactions'
                && $request['enabled_payments'] === ['echannel'];
        });
    }

    public function test_midtrans_notification_marks_invoice_as_paid(): void
    {
        config(['services.midtrans.server_key' => 'SB-Mid-server-test']);

        $user = User::factory()->create([
            'nis' => 'S008',
            'role' => 'santri',
        ]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'name' => 'SPP Semester Ganjil 2026',
            'description' => 'Pembayaran SPP semester ganjil 2026',
            'due_date' => '2026-07-15',
            'amount' => 2200000,
            'penalty' => 0,
            'total' => 2200000,
            'status' => 'belum',
            'payment_method' => 'qris',
            'midtrans_order_id' => 'SPP-TEST-001',
        ]);

        $payload = [
            'order_id' => 'SPP-TEST-001',
            'status_code' => '200',
            'gross_amount' => '2200000.00',
            'transaction_status' => 'settlement',
            'payment_type' => 'qris',
            'transaction_id' => 'trx-test-001',
        ];
        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . 'SB-Mid-server-test'
        );

        $response = $this->postJson('/payment/notification', $payload);

        $response->assertOk();
        $this->assertSame('lunas', $invoice->fresh()->status);
    }

    public function test_payment_data_syncs_overdue_invoice_status(): void
    {
        $user = User::factory()->create([
            'nis' => 'S011',
            'role' => 'santri',
        ]);

        $invoice = Invoice::create([
            'user_id' => $user->id,
            'name' => 'SPP Semester Genap 2026',
            'description' => 'Pembayaran SPP semester genap 2026',
            'due_date' => '2026-01-15',
            'amount' => 2200000,
            'penalty' => 0,
            'total' => 2200000,
            'status' => 'belum',
        ]);

        $data = PaymentController::buildPaymentData($user);

        $this->assertSame('terlambat', $data['invoices'][0]['status']);
        $this->assertSame('terlambat', $invoice->fresh()->status);
        $this->assertGreaterThan(0, $invoice->fresh()->penalty);
    }
}
