<x-guest-layout>
    <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:14px;">
        <div>
            <h1>Welcome back</h1>
            <p style="color:rgba(0,0,0,0.6); margin:4px 0 0;">Use your staff credentials to access the admin.</p>
        </div>
        <a href="/" style="color:#523700; font-weight:700; text-decoration:none;">Back to site</a>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="/login" style="display:grid; gap:12px;">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; margin-top:4px;">
            <label for="remember_me" style="display:flex; align-items:center; gap:8px; color:rgba(0,0,0,0.65);">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="text-sm">{{ __('Remember me') }}</span>
            </label>
            @if (Route::has('password.request'))
                <a style="color:#523700; font-weight:700;" href="{{ route('password.request') }}">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <x-primary-button style="justify-content:center; background:#523700; border-radius:10px; padding:10px 14px; font-weight:700;">
            {{ __('Log in') }}
        </x-primary-button>
    </form>

    <div style="margin-top:14px; color:rgba(0,0,0,0.65);">
        New staff? <a style="color:#523700; font-weight:700;" href="{{ route('register') }}">Request access</a>
    </div>
</x-guest-layout>
