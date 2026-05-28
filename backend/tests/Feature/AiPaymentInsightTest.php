<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiPaymentInsightTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_insight_uses_local_fallback_without_openai_key(): void
    {
        config(['services.openai.api_key' => null]);

        $santri = User::factory()->create([
            'role' => 'santri',
            'nis' => '24 008 847',
        ]);

        Invoice::create([
            'user_id' => $santri->id,
            'name' => 'SPP Semester Genap 2026',
            'due_date' => '2026-01-10',
            'amount' => 2200000,
            'penalty' => 100000,
            'total' => 2300000,
            'status' => 'terlambat',
        ]);

        $response = $this->actingAs($santri)->getJson('/ai/payment-insight');

        $response->assertOk()
            ->assertJsonPath('data.source', 'local')
            ->assertJsonPath('data.risk_label', 'Tinggi');
    }

    public function test_payment_insight_can_use_openai_response(): void
    {
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.model' => 'gpt-5.4-mini',
        ]);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output_text' => json_encode([
                    'risk_score' => 42,
                    'risk_label' => 'Sedang',
                    'reason' => 'Ada tagihan aktif yang perlu dipantau.',
                    'recommendation' => 'Bayar sebelum jatuh tempo agar administrasi tetap aman.',
                    'next_action' => 'Bayar tagihan aktif',
                ]),
            ]),
        ]);

        $santri = User::factory()->create([
            'role' => 'santri',
            'nis' => '24 008 848',
        ]);

        Invoice::create([
            'user_id' => $santri->id,
            'name' => 'SPP Semester Ganjil 2026',
            'due_date' => '2026-07-30',
            'amount' => 2200000,
            'penalty' => 0,
            'total' => 2200000,
            'status' => 'belum',
        ]);

        $response = $this->actingAs($santri)->getJson('/ai/payment-insight');

        $response->assertOk()
            ->assertJsonPath('data.source', 'openai')
            ->assertJsonPath('data.model', 'gpt-5.4-mini')
            ->assertJsonPath('data.risk_score', 42)
            ->assertJsonPath('data.next_action', 'Bayar tagihan aktif');

        Http::assertSentCount(1);
    }
}
