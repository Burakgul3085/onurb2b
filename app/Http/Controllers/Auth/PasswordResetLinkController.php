<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SendPasswordReset;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Auth\AccountCanResetPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     */
    public function store(Request $request, AccountCanResetPassword $canReset, SendPasswordReset $send): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = Str::lower(trim($request->string('email')->toString()));
        $user = User::query()->with('dealer')->whereRaw('lower(email) = ?', [$email])->first();

        if ($user !== null && $canReset->execute($user)) {
            Password::broker()->sendResetLink(
                ['email' => $user->email],
                function (User $account, string $token) use ($send) {
                    $send->execute($account, $token);
                },
            );
        }

        return back()->with('status', __('passwords.sent'));
    }
}
