<?php

namespace Tests\Feature;

use App\Models\XenditPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class XenditGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_xendit_invoice_and_store_payment_record(): void
    {
        config()->set('services.xendit.secret_key', 'xnd_development_key');
        config()->set('services.xendit.api_base_url', 'https://api.xendit.co');

        Http::fake([
            'https://api.xendit.co/v2/invoices' => Http::response([
                'id' => 'inv-123',
                'status' => 'PENDING',
                'invoice_url' => 'https://checkout.xendit.co/inv-123',
            ], 200),
        ]);

        $response = $this->postJson('/xendit/invoices', [
            'location_id' => 'loc-123',
            'amount' => 150000,
            'currency' => 'IDR',
            'payer_email' => 'customer@example.com',
            'description' => 'Test invoice',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('status', 'PENDING');

        $this->assertDatabaseHas('xendit_payments', [
            'location_id' => 'loc-123',
            'xendit_invoice_id' => 'inv-123',
            'status' => 'PENDING',
            'currency' => 'IDR',
        ]);
    }

    public function test_xendit_webhook_requires_valid_callback_token(): void
    {
        config()->set('services.xendit.callback_token', 'token-123');

        $response = $this->postJson('/xendit/webhook', [
            'id' => 'inv-123',
            'status' => 'PAID',
        ], [
            'X-CALLBACK-TOKEN' => 'invalid-token',
        ]);

        $response->assertStatus(401);
    }

    public function test_xendit_webhook_updates_payment_status(): void
    {
        config()->set('services.xendit.callback_token', 'token-123');

        $payment = XenditPayment::query()->create([
            'location_id' => 'loc-123',
            'external_id' => 'ghl-loc-123-uuid',
            'xendit_invoice_id' => 'inv-123',
            'status' => 'PENDING',
            'currency' => 'IDR',
            'amount' => 150000,
        ]);

        $response = $this->postJson('/xendit/webhook', [
            'id' => 'inv-123',
            'external_id' => 'ghl-loc-123-uuid',
            'status' => 'PAID',
            'invoice_url' => 'https://checkout.xendit.co/inv-123',
        ], [
            'X-CALLBACK-TOKEN' => 'token-123',
        ]);

        $response->assertStatus(202);
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('status', 'PAID');

        $payment->refresh();

        $this->assertSame('PAID', $payment->status);
        $this->assertSame('https://checkout.xendit.co/inv-123', $payment->invoice_url);
        $this->assertIsArray($payment->last_webhook_payload);
    }
}
