<?php

namespace App\Http\Controllers;

use App\Models\GhlInstallation;
use App\Services\GhlApiService;
use App\Services\GhlOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class GhlAppController extends Controller
{
    public function __construct(
        private readonly GhlOAuthService $oauthService,
        private readonly GhlApiService $apiService,
    ) {
    }

    public function install(Request $request): RedirectResponse
    {
        $state = $request->query('state');

        return redirect()->away($this->oauthService->getInstallUrl(is_string($state) ? $state : null));
    }

    public function callback(Request $request): View
    {
        $code = $request->query('code');

        if (! is_string($code) || $code === '') {
            return view('ghl.dashboard', [
                'connected' => false,
                'error' => 'Missing authorization code from GoHighLevel.',
            ]);
        }

        try {
            $tokenPayload = $this->oauthService->exchangeCodeForTokens($code);
            $installation = $this->oauthService->persistInstallation($tokenPayload);
            $locationPayload = $this->apiService->getLocation($installation);

            return view('ghl.dashboard', [
                'connected' => true,
                'installation' => $installation,
                'location' => $this->apiService->normalizeLocationData($locationPayload),
            ]);
        } catch (Throwable $exception) {
            return view('ghl.dashboard', [
                'connected' => false,
                'error' => 'Unable to complete GHL authorization. '.$exception->getMessage(),
            ]);
        }
    }

    public function dashboard(Request $request): View
    {
        $locationId = $request->query('locationId');

        if (! is_string($locationId) || $locationId === '') {
            return view('ghl.dashboard', [
                'connected' => false,
                'error' => 'No location selected. Install app from GoHighLevel marketplace to connect.',
            ]);
        }

        $installation = GhlInstallation::query()->where('location_id', $locationId)->first();

        if (! $installation) {
            return view('ghl.dashboard', [
                'connected' => false,
                'error' => 'Installation record not found for this location.',
            ]);
        }

        return view('ghl.dashboard', [
            'connected' => true,
            'installation' => $installation,
        ]);
    }
}
