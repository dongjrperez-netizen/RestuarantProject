<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            Auth::logout();

            return redirect()->route('login')->with('status', 'Your email is already verified. Please sign in to continue.');
        }

        $request->fulfill();

        // Log out the user after verification to ensure they go to login
        Auth::logout();

        return redirect()->route('login')->with('status', 'Your email has been verified successfully! Please sign in to access your account.');
    }
}
