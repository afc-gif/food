<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();
        if (!$user?->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Your account is pending approval. Please contact an admin.',
            ]);
        }

        $role = $user->role ?? '';
        if ($user->hasRole('admin') || $role === 'admin') {
            return redirect()->route('admin');
        }
        if ($user->hasRole('pos') || $role === 'pos') {
            return redirect()->route('pos');
        }
        if ($user->hasRole('kitchen') || $role === 'kitchen') {
            return redirect()->route('kitchen');
        }
        if ($user->hasRole('staff') || $role === 'staff') {
            return redirect()->route('staff');
        }
        if ($user->hasRole('desk') || $role === 'desk') {
            return redirect()->route('home');
        }
        return redirect()->route('home');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
