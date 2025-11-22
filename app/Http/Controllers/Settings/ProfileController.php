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
use Illuminate\Support\Facades\Storage;
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

    /**
     * Update the restaurant settings (logo, etc.)
     */
    public function updateRestaurantSettings(Request $request): RedirectResponse
    {
        // Only restaurant owners can update restaurant settings
        /** @var \App\Models\User $user */
        $user = Auth::guard('web')->user();

        if (!$user) {
            return redirect()->back()->withErrors(['error' => 'Unauthorized access']);
        }

        // Debug logging
        $file = $request->file('logo');
        \Log::info('Upload Request Debug', [
            'hasFile' => $request->hasFile('logo'),
            'file' => $request->file('logo'),
            'fileExists' => $file ? file_exists($file->getPathname()) : null,
            'filePath' => $file ? $file->getPathname() : null,
            'fileName' => $file ? $file->getClientOriginalName() : null,
            'fileSize' => $file ? $file->getSize() : null,
            'fileMime' => $file ? $file->getMimeType() : null,
            'fileExtension' => $file ? $file->getClientOriginalExtension() : null,
            'isValid' => $file ? $file->isValid() : null,
            'error' => $file ? $file->getError() : null,
            'allFiles' => $request->allFiles(),
            'contentType' => $request->header('Content-Type'),
        ]);

        // Validate the request - accept common image formats including webp
        $request->validate([
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'], // Max 2MB
        ]);

        // Get the restaurant data
        $restaurantData = $user->restaurantData;

        if (!$restaurantData) {
            return redirect()->back()->withErrors(['error' => 'Restaurant data not found']);
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            try {
                // Delete old logo if exists
                if ($restaurantData->logo) {
                    Storage::disk('public')->delete($restaurantData->logo);
                }

                // Store the uploaded file directly in the public disk
                // This avoids requiring the GD extension / Intervention Image
                $logoPath = $request->file('logo')->store('restaurant-logos', 'public');

                $restaurantData->logo = $logoPath;

                // Save the changes
                $restaurantData->save();

                return redirect()->back()->with('success', 'Logo uploaded successfully!');
            } catch (\Exception $e) {
                return redirect()->back()->withErrors(['logo' => 'Failed to upload logo: ' . $e->getMessage()]);
            }
        }

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }
}
