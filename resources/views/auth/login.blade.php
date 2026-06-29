<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <x-input-error :messages="$errors->get('email')" class="mb-4" />

    <div class="text-center">
        <h2 class="text-lg font-semibold text-gray-800">Mantenimiento del Auto</h2>
        <p class="mt-1 mb-6 text-sm text-gray-600">Iniciá sesión para registrar combustible, mantenimientos y gastos.</p>

        <a href="{{ route('auth.google.redirect') }}"
           class="inline-flex w-full items-center justify-center gap-3 rounded-md border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.24 1.4-1.66 4.1-5.5 4.1-3.3 0-6-2.74-6-6.1s2.7-6.1 6-6.1c1.88 0 3.14.8 3.86 1.49l2.63-2.54C16.9 3.3 14.7 2.3 12 2.3 6.86 2.3 2.7 6.46 2.7 11.6S6.86 21 12 21c5.46 0 9.07-3.84 9.07-9.25 0-.62-.07-1.1-.16-1.55H12z"/>
            </svg>
            <span>Continuar con Google</span>
        </a>
    </div>
</x-guest-layout>
