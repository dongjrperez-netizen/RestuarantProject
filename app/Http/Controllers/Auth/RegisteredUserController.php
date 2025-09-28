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
use Illuminate\Validation\Rules;
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
        try {
            $validated = $request->validate([
                'last_name' => 'required|string|max:255',
                'first_name' => 'required|string|max:255',
                'middle_name' => 'required|string|max:255',
                'date_of_birth' => 'required|date',
                'gender' => 'required|string|max:255',
                'email' => 'required|string|lowercase|email|max:255|unique:users,email',
                // 'phonenumber' => 'required|string|max:20|unique:users,phonenumber',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],

                'restaurant_name' => 'required|string|max:255',
                'address' => 'required|string|max:255',
                'postal_code' => 'required|string|max:20',
                'contact_number' => 'required|string|max:20|unique:restaurant_data,contact_number',
            ]);

            DB::transaction(function () use ($validated) {

                $user = User::create([
                    'last_name' => $validated['last_name'],
                    'first_name' => $validated['first_name'],
                    'middle_name' => $validated['middle_name'],
                    'date_of_birth' => $validated['date_of_birth'],
                    'gender' => $validated['gender'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                ]);

                Restaurant_Data::create([
                    'user_id' => $user->id,
                    'restaurant_name' => $validated['restaurant_name'],
                    'address' => $validated['address'],
                    'postal_code' => $validated['postal_code'],
                    'contact_number' => $validated['contact_number'],
                ]);
            });

            $user = User::where('email', $validated['email'])->first();
            event(new Registered($user));
            Auth::login($user);

            return redirect()->route('register.documents');
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    public function showDocumentUpload(): Response
    {
        return Inertia::render('auth/Document');
    }

    public function store_doc(Request $request): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'documents' => 'required|array|min:1',
                'documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:5120',
                'document_types' => 'required|array|min:1',
                'document_types.*' => 'string|max:255',
            ]);

            $restaurantId = DB::table('restaurant_data')
                ->where('user_id', auth()->id())
                ->value('id');

            if (! $restaurantId) {
                return redirect()->back()->withErrors([
                    'error' => 'No restaurant linked to your account.',
                ]);
            }

            $uploadedCount = 0;
            $documents = $request->file('documents');
            $documentTypes = $request->input('document_types', []);

            foreach ($documents as $index => $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('documents', 'public');

                    $docType = isset($documentTypes[$index])
                        ? $documentTypes[$index]
                        : $file->getClientOriginalExtension();

                    DB::table('restaurant_documents')->insert([
                        'restaurant_id' => $restaurantId,
                        'doc_type' => $docType,
                        'doc_file' => $file->getClientOriginalName(),
                        'doc_path' => $path,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $uploadedCount++;
                }
            }

            if ($uploadedCount === 0) {
                return redirect()->back()->withErrors([
                    'error' => 'No valid documents were uploaded.',
                ]);
            }

            return redirect()->route('login')->with('success', $uploadedCount.' document(s) uploaded successfully.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors([
                'error' => 'Upload failed: '.$e->getMessage(),
            ]);
        }
    }
}
