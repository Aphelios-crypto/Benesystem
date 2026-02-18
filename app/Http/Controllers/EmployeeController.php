<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    private function getApiBase()
    {
        return config('ihris.api_base_url');
    }

    private function refreshServiceToken()
    {
        $response = Http::post($this->getApiBase() . '/login', [
            'email' => config('ihris.service_email'),
            'password' => config('ihris.service_password'),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $svcToken = $data['token'] ?? $data['access_token'];
            Session::put('service_token', $svcToken);
            return $svcToken;
        }
        
        Log::error('Service Account Login Failed', ['body' => $response->body()]);
        return null; 
    }

    private function getValidToken()
    {
        $token = Session::get('api_token');

        // If local user, use Service Account Token
        if ($token && str_starts_with($token, 'local-token-')) {
            if (Session::has('service_token')) {
                return Session::get('service_token');
            }
            return $this->refreshServiceToken();
        }

        return $token;
    }

    public function dashboard()
    {
        return Inertia::render('Dashboard');
    }

    /**
     * Helper to fetch all pages from a paginated API endpoint
     */
    private function fetchAllPages($url, $token)
    {
        $allData = [];
        $nextUrl = $url;
        $page = 1;

        do {
            Log::debug("[EmployeeController] Fetching page $page", ['url' => $nextUrl]);
            
            $response = Http::withToken($token)
                ->accept('application/json')
                ->get($nextUrl);

            // Handle 401 (Unauthenticated) - Retry logic for local users
            if ($response->status() === 401) {
                $mainToken = Session::get('api_token');
                if ($mainToken && str_starts_with($mainToken, 'local-token-')) {
                    Log::info("Token expired (401), refreshing service token...");
                    $newToken = $this->refreshServiceToken();
                    
                    if ($newToken) {
                        $token = $newToken; // Update token for retry
                        // Retry the failed request
                        $response = Http::withToken($newToken)
                            ->accept('application/json')
                            ->get($nextUrl);
                    }
                }
            }

            if (!$response->successful()) {
                Log::error("Failed to fetch page $page", ['status' => $response->status(), 'body' => $response->body()]);
                if ($page === 1) return $response; 
                break;
            }

            $json = $response->json();
            
            if (isset($json['data']) && is_array($json['data'])) {
                $allData = array_merge($allData, $json['data']);
                $nextUrl = $json['links']['next'] ?? $json['next_page_url'] ?? null;
            } elseif (is_array($json)) {
                $allData = array_merge($allData, $json);
                $nextUrl = null;
            } else {
                $nextUrl = null;
            }

            $page++;
            if ($page > 50) break; 

        } while ($nextUrl);

        return $allData;
    }

    /**
     * Fetch all employees (proxied)
     */
    public function allEmployees(Request $request)
    {
        $token = $this->getValidToken();
        $url = $this->getApiBase() . '/employees';

        $data = $this->fetchAllPages($url, $token);

        if ($data instanceof \Illuminate\Http\Client\Response) {
             return response()->json([
                'error' => 'Failed to fetch employees',
                'status' => $data->status(),
                'body' => $data->json(),
            ], $data->status());
        }

        return response()->json($data);
    }

    /**
     * Fetch ONLY permanent employees (filtered locally)
     */
    public function permanentEmployees(Request $request)
    {
        $token = $this->getValidToken();
        // Use generic endpoint that works, then filter
        $url = $this->getApiBase() . '/employees';

        $data = $this->fetchAllPages($url, $token);

        if ($data instanceof \Illuminate\Http\Client\Response) {
             return response()->json([
                'error' => 'Failed to fetch permanent employees',
                'status' => $data->status(),
                'body' => $data->json(),
            ], $data->status());
        }

        // Filter for Permanent status
        // Check varying case or key naming if unsure, but 'employment_status' is standard
        $filtered = array_filter($data, function ($employee) {
            $status = $employee['employment_status'] ?? $employee['status'] ?? '';
            return stripos($status, 'Permanent') !== false; 
        });

        // Re-index array to be clean JSON list
        return response()->json(array_values($filtered));
    }

    /**
     * Fetch employees by office UUID
     */
    public function employeesByOffice(Request $request, $uuid)
    {
        $token = $this->getValidToken();
        $url = $this->getApiBase() . '/employees/office/' . $uuid;

        $data = $this->fetchAllPages($url, $token);

        if ($data instanceof \Illuminate\Http\Client\Response) {
             return response()->json([
                'error' => 'Failed to fetch employees for office',
                'status' => $data->status(),
                'body' => $data->json(),
            ], $data->status());
        }

        // Filter by Office UUID to ensure correct assignment
        $filtered = array_filter($data, function ($item) use ($uuid) {
            // Check direct keys
            $eUuid = $item['office_uuid'] ?? $item['office_id'] ?? null;
            if ($eUuid === $uuid) return true;

            // Check nested office object
            if (isset($item['office']) && is_array($item['office'])) {
                 $nestedUuid = $item['office']['uuid'] ?? $item['office']['id'] ?? null;
                 if ($nestedUuid === $uuid) return true;
            }
            
            return false;
        });

        return response()->json(array_values($filtered));
    }

    /**
     * Fetch all offices
     */
    /**
     * Display Offices Page
     */
    public function indexOffices()
    {
        $token = $this->getValidToken();
        $url = $this->getApiBase() . '/offices';
        
        $offices = [];
        
        try {
            $response = Http::withToken($token)->get($url);
            if ($response->successful()) {
                $data = $response->json();
                $offices = is_array($data) ? $data : ($data['data'] ?? []);
            }
        } catch (\Exception $e) {
            Log::error('Failed to fetch offices for index', ['error' => $e->getMessage()]);
        }

        return Inertia::render('Offices/Index', [
            'offices' => $offices
        ]);
    }

    /**
     * Fetch offices list (proxied JSON)
     */
    public function offices(Request $request)
    {
        $token = $this->getValidToken();
        $url = $this->getApiBase() . '/offices';

        Log::debug('[EmployeeController] offices called', [
            'url' => $url,
            'has_token' => !empty($token),
        ]);

        $response = Http::withToken($token)
            ->accept('application/json')
            ->get($url);

        if ($response->successful()) {
            return response()->json($response->json());
        }

        return response()->json([
            'error' => 'Failed to fetch offices',
            'status' => $response->status(),
            'body' => $response->json(),
        ], $response->status());
    }
}
