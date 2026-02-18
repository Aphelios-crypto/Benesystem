<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class AuthController extends Controller
{
    const API_BASE = 'https://testihris.bayambang.gov.ph/api';

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

        $response = Http::post(self::API_BASE . '/login', [
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

    public function logout()
    {
        Session::forget(['api_token', 'api_user']);
        return redirect()->route('login');
    }
}
