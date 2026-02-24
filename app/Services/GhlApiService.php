<?php

namespace App\Services;

use App\Models\GhlInstallation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class GhlApiService
{
    public function __construct(private readonly GhlOAuthService $oauthService)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getLocation(GhlInstallation $installation): array
    {
        $installation = $this->getValidInstallation($installation);
        $baseUrl = rtrim((string) config('services.ghl.api_base_url'), '/');

        return Http::baseUrl($baseUrl)
            ->withToken($installation->access_token)
            ->acceptJson()
            ->withHeaders([
                'Version' => '2021-07-28',
            ])
            ->get('/locations/'.urlencode($installation->location_id))
            ->throw()
            ->json();
    }

    private function getValidInstallation(GhlInstallation $installation): GhlInstallation
    {
        if (! $installation->isAccessTokenExpired()) {
            return $installation;
        }

        return $this->oauthService->refreshAccessToken($installation);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function normalizeLocationData(array $payload): array
    {
        $location = Arr::get($payload, 'location', []);

        return [
            'name' => Arr::get($location, 'name'),
            'email' => Arr::get($location, 'email'),
            'phone' => Arr::get($location, 'phone'),
        ];
    }
}
