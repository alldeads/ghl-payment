<?php

namespace Tests\Feature;

use Tests\TestCase;

class GhlAppTest extends TestCase
{
    public function test_ghl_install_route_redirects(): void
    {
        config()->set('services.ghl.client_id', 'test-client');
        config()->set('services.ghl.redirect_uri', 'http://localhost/ghl/callback');
        config()->set('services.ghl.scopes', 'contacts.readonly');
        config()->set('services.ghl.oauth_base_url', 'https://marketplace.gohighlevel.com');

        $response = $this->get('/ghl/install');

        $response->assertRedirect();
        $this->assertStringContainsString('/oauth/chooselocation', $response->headers->get('Location', ''));
    }

    public function test_ghl_dashboard_requires_location_id_query_param(): void
    {
        $response = $this->get('/ghl/dashboard');

        $response->assertOk();
        $response->assertSee('Not connected');
    }

    public function test_webhook_returns_unauthorized_for_invalid_signature(): void
    {
        config()->set('services.ghl.webhook_secret', 'secret-123');

        $response = $this->postJson('/ghl/webhook', ['event' => 'test'], [
            'X-Webhook-Signature' => 'invalid-signature',
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_accepts_valid_signature(): void
    {
        config()->set('services.ghl.webhook_secret', 'secret-123');

        $payload = json_encode(['event' => 'test'], JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $payload, 'secret-123');

        $response = $this->call(
            'POST',
            '/ghl/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
            ],
            $payload,
        );

        $response->assertStatus(202);
    }
}
