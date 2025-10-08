<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Restaurant_Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AccountUpdateController extends Controller
{
    /**
     * Fetch the authenticated user's data.
     */
    public function show()
    {
        $user = Auth::user();

        return Inertia::render('AccountUpdate');
    }

    public function update(Request $request)
    {
        try {
            $user = Auth::user();

            Log::info('AccountUpdate request data:', [
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'has_documents' => $request->hasFile('documents'),
                'document_types' => $request->input('document_types'),
            ]);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'password' => 'nullable|string|min:8',
                'documents' => 'nullable|array',
                'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
                'document_types' => 'nullable|array',
                'document_types.*' => 'string|max:255',
            ]);

            $fullName = trim($validated['name']);
            $nameParts = explode(' ', $fullName);

            $firstName = $nameParts[0] ?? '';
            $lastName = count($nameParts) > 1 ? array_pop($nameParts) : '';
            $middleName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

            $user->first_name = $firstName;
            $user->last_name = $lastName;
            $user->middle_name = $middleName;
            $user->email = $validated['email'];
            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            // If user was rejected and uploading new documents, change status to Pending for re-review
            if ($user->status === 'Rejected' && $request->hasFile('documents')) {
                $user->status = 'Pending';
            }

            $user->save();

            if ($request->hasFile('documents')) {
                $restaurantData = Restaurant_Data::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'restaurant_name' => trim($user->first_name.' '.$user->last_name),
                        'address' => '',
                        'contact_number' => '',
                    ]
                );

                $documents = $request->file('documents');
                $documentTypes = $request->input('document_types', []);

                foreach ($documents as $index => $file) {
                    if ($file && $file->isValid()) {

                        $originalName = $file->getClientOriginalName();
                        $extension = $file->getClientOriginalExtension();
                        $filename = time().'_'.$index.'_'.uniqid().'.'.$extension;

                        $path = $file->storeAs('documents', $filename, 'public');

                        $documentType = $documentTypes[$index] ?? 'General Document';

                        Document::create([
                            'restaurant_id' => $restaurantData->id,
                            'doc_type' => $documentType,
                            'doc_file' => $originalName,
                            'doc_path' => $path,
                        ]);
                    }
                }
            }

            return redirect()->back()->with('success', 'Account and documents updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating account: '.$e->getMessage());
        }
    }

    public function fetchUser(Request $request)
    {
        $user = $request->user();

        return response()->json($user);

    }
}
