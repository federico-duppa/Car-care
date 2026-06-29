<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect the user to Google's OAuth consent screen.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback from Google. Only emails listed in the
     * ALLOWED_EMAILS env var are allowed to sign in (fail closed).
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'No se pudo completar el inicio de sesión con Google. Probá de nuevo.',
            ]);
        }

        $email = strtolower(trim($googleUser->getEmail() ?? ''));

        if (! $this->isAllowed($email)) {
            return redirect()->route('login')->withErrors([
                'email' => 'Esta cuenta de Google no tiene acceso a esta aplicación.',
            ]);
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $googleUser->getName() ?: $email,
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => now(),
            ]
        );

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Check whether an email is in the allow-list. Empty list => deny all.
     */
    private function isAllowed(string $email): bool
    {
        if ($email === '') {
            return false;
        }

        $allowed = collect(explode(',', (string) config('services.allowed_emails')))
            ->map(fn ($e) => strtolower(trim($e)))
            ->filter()
            ->all();

        return in_array($email, $allowed, true);
    }
}
