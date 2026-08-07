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
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return $this->redirectToDashboard($request->user());
        }

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

        return $this->redirectToDashboard($user);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectToDashboard($user): RedirectResponse
    {
        $role = $user->role ?? '';

        if ($user->hasRole('admin') || $role === 'admin') {
            return redirect('/admin');
        }

        if ($user->hasRole('pos') || $role === 'pos') {
            return redirect('/pos');
        }

        if ($user->hasRole('kitchen') || $role === 'kitchen') {
            return redirect('/kitchen');
        }

        if ($user->hasRole('staff') || $user->hasRole('desk') || in_array($role, ['staff', 'desk'], true)) {
            return redirect('/staff');
        }

        return redirect('/staff');
    }
}
