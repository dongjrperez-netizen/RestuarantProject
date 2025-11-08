<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Restaurant_Data;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Increase execution time for email sending (2 minutes)
        set_time_limit(120);

        $validated = $request->validate([
            'last_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\'-]+$/'],
            'first_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\'-]+$/'],
            'middle_name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s\'-]+$/'],
            'date_of_birth' => 'required|date',
            'gender' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            // 'phonenumber' => 'required|string|max:20|unique:users,phonenumber',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],

            'restaurant_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'contact_number' => 'required|string|max:20|unique:restaurant_data,contact_number',
        ], [
            // Personal Information
            'last_name.required' => 'Last name is required.',
            'last_name.regex' => 'Last name can only contain letters, spaces, hyphens, and apostrophes.',
            'first_name.required' => 'First name is required.',
            'first_name.regex' => 'First name can only contain letters, spaces, hyphens, and apostrophes.',
            'middle_name.required' => 'Middle name is required.',
            'middle_name.regex' => 'Middle name can only contain letters, spaces, hyphens, and apostrophes.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.date' => 'Please provide a valid date of birth.',
            'gender.required' => 'Gender is required.',

            // Email & Password
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',

            // Restaurant Information
            'restaurant_name.required' => 'Restaurant name is required.',
            'address.required' => 'Restaurant address is required.',
            'contact_number.required' => 'Contact number is required.',
            'contact_number.unique' => 'This contact number is already registered.',
        ]);

        $user = DB::transaction(function () use ($validated) {

            $user = User::create([
                'last_name' => $validated['last_name'],
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'],
                'date_of_birth' => $validated['date_of_birth'],
                'gender' => $validated['gender'],
                'email' => $validated['email'],
                'password' => $validated['password'], // Laravel 'hashed' cast handles this automatically
            ]);

            Restaurant_Data::create([
                'user_id' => $user->id,
                'restaurant_name' => $validated['restaurant_name'],
                'address' => $validated['address'],
                'postal_code' => $validated['postal_code'],
                'contact_number' => $validated['contact_number'],
            ]);

            return $user;
        });

        $user = User::where('email', $validated['email'])->first();
        event(new Registered($user));
        Auth::login($user);

        return redirect()->route('register.documents');
    }

    public function showDocumentUpload(): Response
    {
        return Inertia::render('auth/Document');
    }

  public function store_doc(Request $request): RedirectResponse
    {
        // Increase execution time for document uploads (max 5 minutes)
        set_time_limit(300);

        Log::info('Document upload request received', [
            'user_id' => auth()->id(),
            'has_files' => $request->hasFile('documents'),
            'files_count' => $request->hasFile('documents') ? count($request->file('documents')) : 0,
            'request_all' => $request->all(),
            'files' => $request->file('documents'),
        ]);

        try {
            // Temporarily simplified validation to debug
            $validated = $request->validate([
                'documents' => 'required|array|min:1',
                'documents.*' => 'required', // Simplified - just check if exists
                'document_types' => 'required|array|min:1',
                'document_types.*' => 'string|max:255',
            ]);

            Log::info('Validation passed!', ['validated' => $validated]);

            $restaurantId = DB::table('restaurant_data')
                ->where('user_id', auth()->id())
                ->value('id');

            if (! $restaurantId) {
                return back()->withErrors(['error' => 'No restaurant linked to your account.']);
            }

            $uploadedCount = 0;
            $documents = $request->file('documents');
            $documentTypes = $request->input('document_types', []);

            $supabaseUrl = env('SUPABASE_URL');
            // Use service role key for uploads (anon key doesn't have upload permissions)
            $supabaseKey = env('SUPABASE_SERVICE_ROLE_KEY');
            $bucket = env('SUPABASE_BUCKET');

            if (!$supabaseUrl || !$supabaseKey || !$bucket) {
                Log::error('Supabase configuration missing', [
                    'SUPABASE_URL' => $supabaseUrl ? 'SET' : 'MISSING',
                    'SUPABASE_KEY' => $supabaseKey ? 'SET' : 'MISSING',
                    'SUPABASE_BUCKET' => $bucket ?? 'MISSING',
                ]);
                return back()->withErrors(['error' => 'Supabase configuration missing. Please set SUPABASE_URL, SUPABASE_KEY, and SUPABASE_BUCKET in .env file.']);
            }

            Log::info('Starting document upload', [
                'file_count' => count($documents),
                'restaurant_id' => $restaurantId,
            ]);

            foreach ($documents as $index => $file) {
                if ($file && $file->isValid()) {
                    // Generate a unique file name
                    $fileName = time() . '_' . $index . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    // Define the storage path within the bucket (e.g., documents/123_unique.jpg)
                    $filePath = "documents/{$fileName}";

                    Log::info("Uploading file to Supabase", [
                        'file' => $fileName,
                        'size' => $file->getSize(),
                    ]);

                    // Upload to Supabase Storage using Laravel Http Client
                    $response = Http::withHeaders([
                            'apikey' => $supabaseKey,
                            'Authorization' => 'Bearer ' . $supabaseKey,
                            'Content-Type' => $file->getMimeType(),
                            'x-upsert' => 'true',
                        ])
                        ->withBody(
                            file_get_contents($file->getRealPath()),
                            $file->getMimeType()
                        )
                        ->post("{$supabaseUrl}/storage/v1/object/{$bucket}/{$filePath}");

                    if ($response->successful()) {
                        // Store info in DB: file name and the full path within the bucket
                        DB::table('restaurant_documents')->insert([
                            'restaurant_id' => $restaurantId,
                            'doc_type' => $documentTypes[$index] ?? 'Other',
                            'doc_file' => $fileName,
                            'doc_path' => $filePath, // This stores 'documents/123_unique.jpg'
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $uploadedCount++;

                        Log::info("File uploaded successfully", ['file' => $fileName]);
                    } else {
                        // Log detailed Supabase upload error
                        $errorBody = $response->body();
                        Log::error('Supabase upload failed', [
                            'status' => $response->status(),
                            'response_body' => $errorBody,
                            'file' => $fileName,
                            'path_attempted' => $filePath,
                            'bucket' => $bucket,
                            'supabase_url' => $supabaseUrl,
                        ]);

                        // Return with detailed error for debugging
                        return back()->withErrors([
                            'error' => "Failed to upload file: " . $file->getClientOriginalName() .
                                      "\nHTTP Status: " . $response->status() .
                                      "\nError: " . $errorBody .
                                      "\nBucket: " . $bucket .
                                      "\nPath: " . $filePath
                        ]);
                    }
                }
            }

            if ($uploadedCount === 0) {
                return back()->withErrors(['error' => 'No valid documents were uploaded or Supabase upload failed for all files. Check logs for details.']);
            }

            Log::info("Document upload completed successfully", [
                'uploaded_count' => $uploadedCount,
                'user_id' => auth()->id(),
                'restaurant_id' => $restaurantId,
            ]);

            return redirect()->route('login')->with('success', "{$uploadedCount} document(s) uploaded successfully. You can now login.");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Document upload exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors(['error' => 'An unexpected error occurred during upload.'])->withInput();
        }
    }


}
