<?php

namespace App\Services;

use App\Models\GhlInstallation;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GhlOAuthService
{
    public function getInstallUrl(?string $state = null): string
    {
        $oauthBaseUrl = rtrim((string) config('services.ghl.oauth_base_url'), '/');

        $query = [
            'response_type' => 'code',
            'client_id' => config('services.ghl.client_id'),
            'redirect_uri' => config('services.ghl.redirect_uri'),
            'scope' => config('services.ghl.scopes'),
            'state' => $state ?: Str::uuid()->toString(),
        ];

        return $oauthBaseUrl.'/oauth/chooselocation?'.http_build_query($query);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws RequestException
     */
    public function exchangeCodeForTokens(string $code): array
    {
        $oauthBaseUrl = rtrim((string) config('services.ghl.oauth_base_url'), '/');

        $response = Http::asForm()->post($oauthBaseUrl.'/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => config('services.ghl.client_id'),
            'client_secret' => config('services.ghl.client_secret'),
            'redirect_uri' => config('services.ghl.redirect_uri'),
            'user_type' => 'Location',
        ])->throw();

        return $response->json();
    }

    /**
     * @throws RequestException
     */
    public function refreshAccessToken(GhlInstallation $installation): GhlInstallation
    {
        if (! $installation->refresh_token) {
            return $installation;
        }

        $oauthBaseUrl = rtrim((string) config('services.ghl.oauth_base_url'), '/');

        $payload = Http::asForm()->post($oauthBaseUrl.'/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $installation->refresh_token,
            'client_id' => config('services.ghl.client_id'),
            'client_secret' => config('services.ghl.client_secret'),
            'user_type' => 'Location',
        ])->throw()->json();

        return $this->persistInstallation($payload);
    }

    /**
     * @param  array<string, mixed>  $tokenPayload
     */
    public function persistInstallation(array $tokenPayload): GhlInstallation
    {
        $locationId = (string) Arr::get($tokenPayload, 'locationId', '');

        $expiresIn = (int) Arr::get($tokenPayload, 'expires_in', 0);
        $expiresAt = $expiresIn > 0 ? Carbon::now()->addSeconds($expiresIn) : null;

        $scopes = Arr::get($tokenPayload, 'scope');
        $scopesValue = is_array($scopes) ? implode(' ', $scopes) : $scopes;

        return GhlInstallation::query()->updateOrCreate(
            ['location_id' => $locationId],
            [
                'company_id' => Arr::get($tokenPayload, 'companyId'),
                'access_token' => (string) Arr::get($tokenPayload, 'access_token', ''),
                'refresh_token' => Arr::get($tokenPayload, 'refresh_token'),
                'expires_at' => $expiresAt,
                'scopes' => $scopesValue,
                'raw_payload' => $tokenPayload,
            ]
        );
    }
}
