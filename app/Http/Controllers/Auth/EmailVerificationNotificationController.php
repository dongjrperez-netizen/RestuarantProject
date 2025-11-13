<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        // Increase execution time for email sending (2 minutes)
        set_time_limit(120);

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }

    /**
     * Resend email verification notification for unverified users (public endpoint)
     */
    public function resendPublic(Request $request): RedirectResponse
    {
        // Increase execution time for email sending (2 minutes)
        set_time_limit(120);

        $request->validate([
            'email' => 'required|email',
        ]);

        $email = $request->email;

        // Rate limiting to prevent abuse
        $key = 'resend-verification:' . $email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Too many verification requests. Please try again in {$seconds} seconds.",
            ]);
        }

        // Find the user
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Don't reveal if email exists or not for security
            return back()->with('status', 'If an account exists with this email, a verification link has been sent.');
        }

        if ($user->hasVerifiedEmail()) {
            return back()->with('status', 'This email is already verified. You can log in now.');
        }

        // Send verification email
        $user->sendEmailVerificationNotification();
        RateLimiter::hit($key, 300); // 5 minute cooldown

        return back()->with('status', 'A verification link has been sent to your email address.');
    }
}
