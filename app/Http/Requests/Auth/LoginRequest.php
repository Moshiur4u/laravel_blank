<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }
    
        // ==========================================
        // ইউজার অ্যাক্টিভ কিনা তা চেক করার নতুন কোড
        // ==========================================
        $user = Auth::user();
        
        // এখানে status 0 হলে ইনঅ্যাক্টিভ ধরা হচ্ছে
        if ($user && $user->status == 0) { 
            Auth::logout(); // লগিন হয়ে গেছে, তাই লগআউট করে দিন
            
            // সেশন ক্লিয়ার করে দিন (নিরাপত্তার জন্য)
            $this->session()->invalidate();
            $this->session()->regenerateToken();

            // কাস্টম এরর মেসেজ দেখান
            throw ValidationException::withMessages([
                'email' => 'আপনার অ্যাকাউন্টটি ইনঅ্যাক্টিভ। লগিন করতে অ্যাডমিনের সাথে যোগাযোগ করুন।',
            ]);
        }
        // ==========================================

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
