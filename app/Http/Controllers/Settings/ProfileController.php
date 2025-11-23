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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
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
        Log::info('Logo Upload Request Debug', [
            'hasFile' => $request->hasFile('logo'),
            'user_id' => $user->id,
            'fileName' => $file ? $file->getClientOriginalName() : null,
            'fileSize' => $file ? $file->getSize() : null,
            'fileMime' => $file ? $file->getMimeType() : null,
        ]);

        // Validate the request - accept common image formats including webp
        $request->validate([
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:5120'], // Max 5MB
        ]);

        // Get the restaurant data
        $restaurantData = $user->restaurantData;

        if (!$restaurantData) {
            return redirect()->back()->withErrors(['error' => 'Restaurant data not found']);
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            try {
                // Upload to Supabase instead of local storage
                $uploadedFile = $request->file('logo');
                $publicUrl = $this->uploadLogoToSupabase($uploadedFile, $user->id);

                if (!$publicUrl) {
                    return redirect()->back()->withErrors(['logo' => 'Failed to upload logo to cloud storage.']);
                }

                // Delete old logo from Supabase if exists
                if ($restaurantData->logo && str_contains($restaurantData->logo, 'supabase')) {
                    $this->deleteLogoFromSupabase($restaurantData->logo);
                }

                // Save the Supabase URL
                $restaurantData->logo = $publicUrl;
                $restaurantData->save();

                Log::info('Logo uploaded successfully to Supabase', [
                    'user_id' => $user->id,
                    'url' => $publicUrl,
                ]);

                return redirect()->back()->with('success', 'Logo uploaded successfully!');
            } catch (\Exception $e) {
                Log::error('Logo upload failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                return redirect()->back()->withErrors(['logo' => 'Failed to upload logo: ' . $e->getMessage()]);
            }
        }

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }

    /**
     * Upload logo to Supabase Storage
     */
    private function uploadLogoToSupabase($file, $userId)
    {
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = env('SUPABASE_SERVICE_ROLE_KEY') ?? env('SUPABASE_KEY');
        $bucket = env('SUPABASE_BUCKET_DISH'); // Using the same bucket as dishes

        if (!$supabaseUrl || !$supabaseKey || !$bucket || !$file) {
            Log::error('Supabase configuration missing for logo upload.', [
                'has_url' => !empty($supabaseUrl),
                'has_key' => !empty($supabaseKey),
                'has_bucket' => !empty($bucket),
                'has_file' => !empty($file),
            ]);
            return null;
        }

        try {
            $fileName = 'logo_' . $userId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = "images/restaurant-logos/{$fileName}";

            Log::info('Uploading logo to Supabase', [
                'bucket' => $bucket,
                'file_path' => $filePath,
                'user_id' => $userId,
            ]);

            // Upload to Supabase Storage
            $response = Http::withHeaders([
                'apikey' => $supabaseKey,
                'Authorization' => 'Bearer ' . $supabaseKey,
                'Content-Type' => $file->getMimeType(),
                'x-upsert' => 'true',
            ])->withBody(
                file_get_contents($file->getRealPath()),
                $file->getMimeType()
            )->post("{$supabaseUrl}/storage/v1/object/{$bucket}/{$filePath}");

            if ($response->successful()) {
                Log::info('Logo uploaded successfully to Supabase.', [
                    'user_id' => $userId,
                    'path' => $filePath,
                ]);

                // Construct and return the public URL
                $url = rtrim($supabaseUrl, '/');
                return "{$url}/storage/v1/object/public/{$bucket}/{$filePath}";
            } else {
                Log::error('Supabase logo upload failed.', [
                    'user_id' => $userId,
                    'status' => $response->status(),
                    'response_body' => $response->body(),
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Supabase logo upload exception.', [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Delete logo from Supabase Storage
     */
    private function deleteLogoFromSupabase($logoUrl)
    {
        try {
            $supabaseUrl = env('SUPABASE_URL');
            $supabaseKey = env('SUPABASE_SERVICE_ROLE_KEY') ?? env('SUPABASE_KEY');
            $bucket = env('SUPABASE_BUCKET_DISH');

            if (!$supabaseUrl || !$supabaseKey || !$bucket) {
                return;
            }

            // Extract the file path from the URL
            // URL format: https://[project].supabase.co/storage/v1/object/public/[bucket]/[path]
            $pattern = "/\/storage\/v1\/object\/public\/{$bucket}\/(.*)/";
            if (preg_match($pattern, $logoUrl, $matches)) {
                $filePath = $matches[1];

                Http::withHeaders([
                    'apikey' => $supabaseKey,
                    'Authorization' => 'Bearer ' . $supabaseKey,
                ])->delete("{$supabaseUrl}/storage/v1/object/{$bucket}/{$filePath}");

                Log::info('Old logo deleted from Supabase', ['path' => $filePath]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to delete old logo from Supabase', [
                'error' => $e->getMessage(),
            ]);
            // Don't fail the upload if delete fails
        }
    }
}
