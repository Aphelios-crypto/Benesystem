<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Session::has('api_token')) {
            return redirect()->route('dashboard');
        }
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // 1. Try Local Database Login
        if (\Illuminate\Support\Facades\Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->boolean('remember'))) {
            $user = \Illuminate\Support\Facades\Auth::user();
            
            // Satisfy ApiAuthenticated middleware & legacy session usage
            Session::put('api_token', 'local-token-' . $user->id);
            Session::put('api_user', $user->toArray());

            return redirect()->route('dashboard');
        }

        // 2. Fallback to API Login
        $apiUrl = config('ihris.api_base_url') . '/login';
        
        $response = Http::post($apiUrl, [
            'email'    => $request->email,
            'password' => $request->password,
        ]);

        if ($response->successful()) {
            $data = $response->json();

            // Store token and user info in session
            Session::put('api_token', $data['token'] ?? $data['access_token'] ?? null);
            Session::put('api_user', $data['user'] ?? $data['data'] ?? $data);

            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'email' => $response->json('message') ?? 'Invalid credentials. Please try again.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        Session::forget(['api_token', 'api_user']);
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
