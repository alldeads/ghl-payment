<?php

namespace Tests\Feature;

use App\Models\XenditPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class XenditGatewayTest extends TestCase
{
    use RefreshDatabase;

    public function test_xendit_test_page_is_accessible(): void
    {
        config()->set('services.xendit.secret_key', 'xnd_development_key');
        config()->set('services.xendit.api_base_url', 'https://api.xendit.co');

        Http::fake([
            'https://api.xendit.co/v2/invoices*' => Http::response([
                [
                    'id' => 'inv-list-1',
                    'external_id' => 'ext-list-1',
                    'amount' => 10000,
                    'currency' => 'IDR',
                    'status' => 'PENDING',
                ],
            ], 200),
        ]);

        $response = $this->get('/xendit/test');

        $response->assertOk();
        $response->assertSee('Xendivel Checkout Example');
    }

    public function test_can_create_xendit_invoice_from_api_flow(): void
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
        $response->assertJsonPath('xendit_invoice_id', 'inv-123');
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

    public function test_simulated_checkout_page_is_accessible(): void
    {
        $response = $this->get('/xendit/test/checkout/inv-789');

        $response->assertOk();
        $response->assertSee('Xendivel Checkout Example');
    }

    public function test_native_pay_with_card_endpoint_returns_api_response(): void
    {
        config()->set('services.xendit.secret_key', 'xnd_development_key');
        config()->set('services.xendit.api_base_url', 'https://api.xendit.co');

        Http::fake([
            'https://api.xendit.co/credit_card_charges' => Http::response([
                'status' => 'CAPTURED',
                'id' => 'cc-charge-1',
            ], 200),
        ]);

        $response = $this->postJson('/pay-with-card', [
            'amount' => 250000,
            'token_id' => 'tok_123',
            'authentication_id' => 'auth_123',
            'currency' => 'IDR',
            'external_id' => 'ghl-loc-999-uuid',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'CAPTURED');
    }

    public function test_native_pay_via_qr_endpoint_returns_api_response(): void
    {
        config()->set('services.xendit.secret_key', 'xnd_development_key');
        config()->set('services.xendit.api_base_url', 'https://api.xendit.co');
        config()->set('services.xendit.callback_url', 'https://example.test/xendit/webhook');

        Http::fake([
            'https://api.xendit.co/qr_codes' => Http::response([
                'id' => 'qr_123',
                'external_id' => 'qr-ext-123',
                'type' => 'DYNAMIC',
                'status' => 'ACTIVE',
                'qr_string' => '0002010102112657ID.CO.QRIS.WWW01189360091530000000000000000003UMI51440014ID.CO.QRIS.WWW0215ID10243384736510303UMI5204541153033605802ID5914TEST MERCHANT6007JAKARTA6105123406304ABCD',
            ], 200),
        ]);

        $response = $this->postJson('/pay-via-qr', [
            'amount' => 25000,
            'currency' => 'IDR',
            'type' => 'DYNAMIC',
        ]);

        $response->assertOk();
        $response->assertJsonPath('type', 'DYNAMIC');
        $response->assertJsonPath('status', 'ACTIVE');
    }

    public function test_native_qr_simulation_endpoint_returns_api_response(): void
    {
        config()->set('services.xendit.secret_key', 'xnd_development_key');
        config()->set('services.xendit.api_base_url', 'https://api.xendit.co');

        Http::fake([
            'https://api.xendit.co/qr_codes/qr_123/payments/simulate' => Http::response([
                'id' => 'qr-pay-123',
                'status' => 'COMPLETED',
                'qr_id' => 'qr_123',
            ], 200),
        ]);

        $response = $this->postJson('/pay-via-qr/simulate', [
            'qr_code_id' => 'qr_123',
        ]);

        $response->assertOk();
        $response->assertJsonPath('status', 'COMPLETED');
        $response->assertJsonPath('qr_id', 'qr_123');
    }
}
