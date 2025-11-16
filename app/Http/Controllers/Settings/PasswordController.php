<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Show the user's password settings page.
     */
    public function edit(): Response
    {
        return Inertia::render('settings/Password');
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        // Determine which guard is active and validate the current password accordingly
        $guard = Auth::guard('employee')->check() ? 'employee' : null;

        $rules = [
            'current_password' => ['required', $guard ? 'current_password:'.$guard : 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];

        $validated = $request->validate($rules);

        $hashedPassword = Hash::make($validated['password']);

        $owner = Auth::guard('web')->user();
        $employee = Auth::guard('employee')->user();

        if ($employee && ! $owner) {
            // Manager / employee changing their own password
            $employee->update([
                'password' => $hashedPassword,
            ]);

            // Optionally keep linked owner record (if any) in sync
            if ($employee->user_id) {
                /** @var \App\Models\User|null $linkedOwner */
                $linkedOwner = User::find($employee->user_id);
                if ($linkedOwner && $linkedOwner->email === $employee->email) {
                    $linkedOwner->update([
                        'password' => $hashedPassword,
                    ]);
                }
            }
        } else {
            // Default behaviour: restaurant owner changing their password
            $user = $owner ?: $request->user();
            $user->update([
                'password' => $hashedPassword,
            ]);

            // If a manager/supervisor employee is also logged in, keep their password in sync
            if (Auth::guard('employee')->check()) {
                $employeeUser = Auth::guard('employee')->user();

                if ($employeeUser && $employeeUser->user_id === $user->id && $employeeUser->email === $user->email) {
                    $employeeUser->update([
                        'password' => $hashedPassword,
                    ]);
                }
            }
        }

        return back();
    }
}
