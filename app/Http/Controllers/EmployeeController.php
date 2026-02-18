<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    const API_BASE = 'https://testihris.bayambang.gov.ph/api';

    private function getToken()
    {
        return Session::get('api_token');
    }

    public function dashboard()
    {
        $user = Session::get('api_user');
        return Inertia::render('Dashboard', [
            'authUser' => $user,
        ]);
    }

    /**
     * Get all employees (including OJTs) by office UUID
     */
    public function allEmployees(Request $request, $officeUuid)
    {
        $token = $this->getToken();
        $url = self::API_BASE . "/all-employees/office/{$officeUuid}";

        Log::debug('[EmployeeController] allEmployees called', [
            'url'        => $url,
            'has_token'  => !empty($token),
            'token_preview' => $token ? substr($token, 0, 20) . '...' : null,
        ]);

        $response = Http::withToken($token)
            ->accept('application/json')
            ->get($url);

        Log::debug('[EmployeeController] allEmployees response', [
            'status'  => $response->status(),
            'headers' => $response->headers(),
            'body'    => $response->body(),
        ]);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json([
            'error'  => 'Failed to fetch employees',
            'status' => $response->status(),
            'body'   => $response->json(),
        ], $response->status());
    }

    /**
     * Get permanent employees
     */
    public function permanentEmployees(Request $request)
    {
        $token = $this->getToken();
        $url = self::API_BASE . '/employees';

        Log::debug('[EmployeeController] permanentEmployees called', [
            'url'        => $url,
            'has_token'  => !empty($token),
            'token_preview' => $token ? substr($token, 0, 20) . '...' : null,
        ]);

        $response = Http::withToken($token)
            ->accept('application/json')
            ->get($url);

        Log::debug('[EmployeeController] permanentEmployees response', [
            'status'  => $response->status(),
            'body'    => $response->body(),
        ]);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json([
            'error'  => 'Failed to fetch users',
            'status' => $response->status(),
            'body'   => $response->json(),
        ], $response->status());
    }

    /**
     * Get list of offices
     */
    public function offices(Request $request)
    {
        $token = $this->getToken();
        $url = self::API_BASE . '/offices';

        Log::debug('[EmployeeController] offices called', [
            'url'        => $url,
            'has_token'  => !empty($token),
            'token_preview' => $token ? substr($token, 0, 20) . '...' : null,
        ]);

        $response = Http::withToken($token)
            ->accept('application/json')
            ->get($url);

        Log::debug('[EmployeeController] offices response', [
            'status'  => $response->status(),
            // 'body'    => $response->body(), // Too verbose if many offices
        ]);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json([
            'error'  => 'Failed to fetch offices',
            'status' => $response->status(),
            'body'   => $response->json(),
        ], $response->status());
    }
}
