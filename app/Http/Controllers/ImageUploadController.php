<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        Log::info('Image upload started (Supabase version)', [
            'user_id' => auth()->id(),
            'type' => $request->input('type')
        ]);

        try {
            $validation = $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'type' => 'required|string'
            ]);

            $uploadedFile = $request->file('image');
            $type = $request->input('type', 'general');

            // --- CHANGED LOGIC: Use Supabase Upload ---
            // Use the SUPABASE_BUCKET_DISH and a folder structure based on the type
            $bucketEnvKey = 'SUPABASE_BUCKET_DISH';
            $folder = 'images/' . $type;
            
            // Call the Supabase upload helper
            $publicUrl = $this->uploadImageToSupabase($uploadedFile, $bucketEnvKey, $folder);

            if (!$publicUrl) {
                // Return a generic server error if Supabase upload failed
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload image to external storage (Supabase).'
                ], 500);
            }

            Log::info('Image upload successful', [
                'url' => $publicUrl,
            ]);

            // Return the full public Supabase URL
            return response()->json([
                'success' => true,
                'url' => $publicUrl,
                'path' => $publicUrl, // Return the URL in both fields for front-end compatibility
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
             // Return validation errors as JSON
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('General Image upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected server error occurred during upload.'
            ], 500);
        }
    }

    private function uploadImageToSupabase($file, $bucketEnvKey, $folder)
    {
        $supabaseUrl = env('SUPABASE_URL');
        // Use service role key for better permissions when uploading
        $supabaseKey = env('SUPABASE_SERVICE_ROLE_KEY') ?? env('SUPABASE_KEY');
        $bucket = env($bucketEnvKey);

        if (!$supabaseUrl || !$supabaseKey || !$bucket || !$file) {
            Log::error('Supabase configuration or file missing for upload.', [
                'bucket_key' => $bucketEnvKey,
                'has_url' => !empty($supabaseUrl),
                'has_key' => !empty($supabaseKey),
                'has_bucket' => !empty($bucket),
                'has_file' => !empty($file),
            ]);
            return null;
        }

        try {
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            // Path structure: [folder]/[filename]
            $filePath = "{$folder}/{$fileName}";

            Log::info('Attempting to upload image to Supabase', [
                'bucket' => $bucket,
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType(),
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
                Log::info('Image uploaded successfully to Supabase.', [
                    'bucket' => $bucket,
                    'path_saved' => $filePath,
                    'response' => $response->json(),
                ]);

                // Construct and return the public URL
                $url = rtrim($supabaseUrl, '/');
                return "{$url}/storage/v1/object/public/{$bucket}/{$filePath}";
            } else {
                Log::error('Supabase image upload failed.', [
                    'status' => $response->status(),
                    'response_body' => $response->body(),
                    'response_json' => $response->json(),
                    'file' => $fileName,
                    'bucket' => $bucket,
                    'path' => $filePath,
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Supabase upload exception.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }
}