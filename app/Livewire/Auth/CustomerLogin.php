<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;

class CustomerLogin extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    /**
     * Max failed login attempts allowed before a temporary lockout kicks in.
     * Guest bookings auto-provision accounts (see BookAppointmentAction), so this
     * throttle is what stands between a leaked/guessed email and a brute-force
     * attempt against it — keep it in place even if login itself feels low-risk.
     */
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function login()
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Throttle key is per email+IP (not just email) so one bad actor can't lock
        // a real customer out just by submitting their email repeatedly from elsewhere.
        $throttleKey = Str::lower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('email', "Too many login attempts. Please try again in {$seconds} seconds.");

            return;
        }

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::clear($throttleKey);
            session()->regenerate();

            return redirect()->intended(route('portal.dashboard'));
        }

        RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
        $this->addError('email', 'The provided credentials do not match our records.');
    }

    public function render()
    {
        return view('livewire.auth.customer-login')
            ->layout('components.layouts.public', ['title' => 'Customer Login · TrueWrench']);
    }
}
