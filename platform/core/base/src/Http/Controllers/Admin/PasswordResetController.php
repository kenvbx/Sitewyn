<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Sitewyn\Core\Base\Http\Requests\AdminForgotPasswordRequest;
use Sitewyn\Core\Base\Http\Requests\AdminResetPasswordRequest;

class PasswordResetController extends Controller
{
    public function showForgotPasswordForm(): View
    {
        return view('core/base::admin.auth.forgot-password');
    }

    public function sendResetLink(AdminForgotPasswordRequest $request): RedirectResponse
    {
        $status = Password::broker()->sendResetLink($request->validated());

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return back()->with('status', __($status));
    }

    public function showResetPasswordForm(string $token): View
    {
        return view('core/base::admin.auth.reset-password', [
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    public function resetPassword(AdminResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::broker()->reset(
            $request->validated(),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return redirect()->route('admin.login')->with('status', __($status));
    }
}
