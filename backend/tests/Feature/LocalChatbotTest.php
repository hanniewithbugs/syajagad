<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_santri_can_check_total_tagihan_from_own_invoices(): void
    {
        $santri = User::factory()->create(['role' => 'santri', 'nis' => '24 008 900']);

        Invoice::create([
            'user_id' => $santri->id,
            'name' => 'SPP Semester Ganjil 2026',
            'due_date' => '2026-07-20',
            'amount' => 2200000,
            'penalty' => 100000,
            'total' => 2300000,
            'status' => 'belum',
        ]);

        $response = $this->actingAs($santri)->postJson('/chatbot/quick', [
            'intent' => 'total_tagihan',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.summary.active_total', 2300000)
            ->assertJsonFragment(['message' => 'Total tagihan aktif kamu saat ini Rp 2.300.000 dari 1 tagihan. Buka menu Tagihan untuk memilih pembayaran lewat Midtrans.']);
    }

    public function test_chatbot_keeps_other_santri_invoices_private(): void
    {
        $firstSantri = User::factory()->create(['role' => 'santri', 'nis' => '24 008 901']);
        $secondSantri = User::factory()->create(['role' => 'santri', 'nis' => '24 008 902']);

        Invoice::create([
            'user_id' => $firstSantri->id,
            'name' => 'SPP Semester Ganjil 2026',
            'due_date' => '2026-07-20',
            'amount' => 100000,
            'penalty' => 0,
            'total' => 100000,
            'status' => 'belum',
        ]);

        Invoice::create([
            'user_id' => $secondSantri->id,
            'name' => 'SPP Semester Ganjil 2026',
            'due_date' => '2026-07-20',
            'amount' => 9000000,
            'penalty' => 0,
            'total' => 9000000,
            'status' => 'belum',
        ]);

        $response = $this->actingAs($firstSantri)->postJson('/chatbot/quick', [
            'intent' => 'total_tagihan',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.summary.active_total', 100000)
            ->assertJsonMissing(['active_total' => 9000000]);
    }

    public function test_chatbot_can_answer_penalty_and_latest_payment(): void
    {
        $santri = User::factory()->create(['role' => 'santri', 'nis' => '24 008 903']);

        Invoice::create([
            'user_id' => $santri->id,
            'name' => 'SPP Semester Genap 2026',
            'due_date' => '2026-01-20',
            'amount' => 2200000,
            'penalty' => 100000,
            'total' => 2300000,
            'status' => 'terlambat',
        ]);

        Invoice::create([
            'user_id' => $santri->id,
            'name' => 'SPP Semester Ganjil 2025',
            'due_date' => '2025-07-20',
            'amount' => 2200000,
            'penalty' => 0,
            'total' => 2200000,
            'status' => 'lunas',
            'payment_method' => 'qris',
            'paid_date' => '2026-03-18',
        ]);

        $this->actingAs($santri)->postJson('/chatbot/quick', [
            'intent' => 'denda',
        ])->assertOk()
            ->assertJsonPath('data.summary.penalty_total', 100000);

        $this->actingAs($santri)->postJson('/chatbot/quick', [
            'intent' => 'status_terakhir',
        ])->assertOk()
            ->assertJsonFragment(['intent' => 'status_terakhir'])
            ->assertSee('SPP Semester Ganjil 2025');
    }

    public function test_chatbot_rejects_unknown_intent(): void
    {
        $santri = User::factory()->create(['role' => 'santri', 'nis' => '24 008 904']);

        $this->actingAs($santri)->postJson('/chatbot/quick', [
            'intent' => 'lihat_tagihan_orang_lain',
        ])->assertUnprocessable();
    }
}
