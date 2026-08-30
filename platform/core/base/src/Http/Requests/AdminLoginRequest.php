<?php

namespace Sitewyn\Core\Base\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AdminLoginRequest extends FormRequest
{
    /**
     * Maximum failed login attempts per email + IP pair.
     */
    protected int $maxAttempts = 5;

    /**
     * Lockout window in seconds.
     */
    protected int $decaySeconds = 60;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Block the attempt when this email + IP pair is locked out.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $this->maxAttempts)) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => __('Too many login attempts. Please try again in :seconds seconds.', [
                'seconds' => RateLimiter::availableIn($this->throttleKey()),
            ]),
        ]);
    }

    /**
     * Record a failed login attempt for this email + IP pair.
     */
    public function hitRateLimit(): void
    {
        RateLimiter::hit($this->throttleKey(), $this->decaySeconds);
    }

    /**
     * Clear the counter after a successful login.
     */
    public function clearRateLimit(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    protected function throttleKey(): string
    {
        return sha1(strtolower((string) $this->input('email')).'|'.$this->ip());
    }
}
