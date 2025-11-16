<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        // Determine the currently authenticated account (owner or employee)
        $owner = Auth::guard('web')->user();
        $employee = Auth::guard('employee')->user();
        $activeUser = $employee ?: $owner;

        return Inertia::render('settings/Profile', [
            'mustVerifyEmail' => $activeUser instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $owner = Auth::guard('web')->user();
        $employee = Auth::guard('employee')->user();

        if ($employee && ! $owner) {
            // Manager / employee updating their own profile
            $employee->firstname = $validated['first_name'];
            $employee->lastname = $validated['last_name'];
            $employee->middlename = $validated['middle_name'] ?? null;
            $employee->date_of_birth = $validated['date_of_birth'];
            $employee->gender = strtolower($validated['gender']);

            if ($employee->email !== $validated['email']) {
                $employee->email_verified_at = null;
            }

            $employee->email = $validated['email'];
            $employee->save();

            // Optionally keep linked owner record (if any) in sync
            if ($employee->user_id) {
                /** @var \App\Models\User|null $linkedOwner */
                $linkedOwner = User::find($employee->user_id);
                if ($linkedOwner) {
                    $linkedOwner->first_name = $validated['first_name'];
                    $linkedOwner->last_name = $validated['last_name'];
                    $linkedOwner->middle_name = $validated['middle_name'] ?? null;
                    $linkedOwner->date_of_birth = $validated['date_of_birth'];
                    $linkedOwner->gender = $validated['gender'];

                    if ($linkedOwner->email !== $validated['email']) {
                        $linkedOwner->email_verified_at = null;
                    }

                    $linkedOwner->email = $validated['email'];
                    $linkedOwner->save();
                }
            }
        } else {
            // Default behaviour: restaurant owner updating their profile
            /** @var \App\Models\User $user */
            $user = $owner ?: $request->user();

            // Update the primary authenticated user (restaurant owner)
            $user->fill($validated);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            // If a manager/supervisor employee is also logged in, keep their record in sync
            if (Auth::guard('employee')->check()) {
                /** @var \App\Models\Employee $employeeUser */
                $employeeUser = Auth::guard('employee')->user();

                // Only sync when this employee actually belongs to this owner and shares the same email
                if ($employeeUser && $employeeUser->user_id === $user->id && $employeeUser->email === $user->email) {
                    $employeeUser->update([
                        'firstname' => $user->first_name,
                        'lastname' => $user->last_name,
                        'middlename' => $user->middle_name,
                        'date_of_birth' => $user->date_of_birth,
                        // Employee validation expects lowercase gender (male/female/other)
                        'gender' => $user->gender ? strtolower($user->gender) : null,
                        'email' => $user->email,
                    ]);
                }
            }
        }

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Account deletion is limited to the main restaurant owner account
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::guard('web')->user() ?: $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
