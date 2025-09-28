<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        \Log::info('Image upload started', [
            'user_id' => auth()->id(),
            'files' => $request->allFiles(),
            'type' => $request->input('type')
        ]);

        try {
            $validation = $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'type' => 'required|string'
            ]);

            \Log::info('Validation data', $validation);

            \Log::info('Image upload validation passed');

            $image = $request->file('image');
            $type = $request->input('type', 'general');

            // Generate unique filename
            $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();

            // Store in public/storage/images/{type} directory
            $path = $image->storeAs("images/{$type}", $filename, 'public');

            // Generate URL
            $url = Storage::url($path);

            \Log::info('Image upload successful', [
                'url' => $url,
                'path' => $path,
                'filename' => $filename
            ]);

            return response()->json([
                'success' => true,
                'url' => $url,
                'path' => $path,
                'filename' => $filename
            ]);

        } catch (\Exception $e) {
            \Log::error('Image upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload image: ' . $e->getMessage()
            ], 500);
        }
    }
}