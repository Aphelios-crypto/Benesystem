<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class TestProxyController extends EmployeeController
{
    /**
     * Override to use TEST API BASE URL
     */
    protected function getApiBase()
    {
        return config('ihris.test_api_base_url');
    }

    /**
     * Override token logic if test environment uses different credentials?
     * For now, assume SAME credentials work on TEST environment if it's a clone.
     * But usually test environment has different tokens.
     * 
     * If credentials are different, we need config('ihris.test_service_email') etc.
     * But user only asked for API URL.
     * Assuming the live login works or we use local login logic.
     * 
     * However, the parent `getValidToken` uses `config('ihris.api_base_url')` for login call.
     * I need to override `getValidToken` too if the login endpoint is on Test server.
     */
    protected function getValidToken()
    {
        $token = Session::get('test_api_token'); // Use separate session key for test

        // If local user
        // We need a test service account token
        if (Session::has('test_service_token')) {
            return Session::get('test_service_token');
        }

        // Authenticate Service Account against TEST server
        $response = Http::post($this->getApiBase() . '/login', [
            'email' => config('ihris.service_email'), // Assuming same credentials
            'password' => config('ihris.service_password'),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $svcToken = $data['token'] ?? $data['access_token'];
            Session::put('test_service_token', $svcToken);
            return $svcToken;
        }
        
        Log::error('Test Service Account Login Failed', ['body' => $response->body()]);
        return null;
    }

    /**
     * Generic proxy for testing any endpoint
     * Route: /test-api/{endpoint}
     */
    public function handle(Request $request, $endpoint)
    {
        $token = $this->getValidToken();
        $url = $this->getApiBase() . '/' . $endpoint;

        // Propagate query params
        $query = $request->query();
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        Log::debug('[TestProxy] Requesting', ['url' => $url]);

        $response = Http::withToken($token)
            ->accept('application/json')
            ->get($url);

        return response()->json($response->json(), $response->status());
    }
}
