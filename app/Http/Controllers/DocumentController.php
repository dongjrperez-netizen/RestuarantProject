<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use Inertia\Inertia;

class DocumentController extends Controller
{
    public function index()
    {
        $pendingApplications = $this->getApplicationsByStatus('Pending');

        return Inertia::render('Admin/PendingAccounts', [
            'pendingApplications' => $pendingApplications,
        ]);
    }

      /**
     * Generate Supabase public URL for a file
     */
       private function getSupabasePublicUrl($path)
    {
        // 1. Ensure the path is not null and configurations are set
        if (!$path) {
            return null;
        }

        $supabaseUrl = env('SUPABASE_URL');
        $bucket = env('SUPABASE_BUCKET'); // CRITICAL: Must match the bucket name on Supabase

        if (!$supabaseUrl || !$bucket) {
            Log::error('Supabase URL or BUCKET is missing when trying to generate public URL.');
            return null;
        }
        
        $url = rtrim($supabaseUrl, '/');
        
        // Ensure the path does not have a leading slash, as it's already included
        // in the URL structure. Your doc_path should already be clean, but this ensures robustness.
        $cleanPath = ltrim($path, '/');

        // 2. Construct the URL in the standard Supabase public format:
        // [SUPABASE_URL]/storage/v1/object/public/[BUCKET_NAME]/[FILE_PATH]
        $publicUrl = "{$url}/storage/v1/object/public/{$bucket}/{$cleanPath}";

        // Log the generated URL for debugging purposes
        // Log::info('Generated Supabase Public URL: ' . $publicUrl);
        
        return $publicUrl;
    }

    /**
     * Fetches applications and maps documents to their public Supabase URLs.
     */
    private function getApplicationsByStatus($status = null)
    {
        $query = DB::table('restaurant_documents')
            ->join('restaurant_data', 'restaurant_documents.restaurant_id', '=', 'restaurant_data.id')
            ->join('users', 'restaurant_data.user_id', '=', 'users.id');

        if ($status) {
            $query->where('users.status', $status);
        }

        $applications = $query->select([
            'restaurant_data.id as restaurant_id',
            'users.id as user_id',
            'restaurant_data.restaurant_name',
            DB::raw("CONCAT(users.first_name, ' ', users.last_name) as user_name"),
            'users.email as contact_email',
            'users.status',
        ])
            ->get()
            ->groupBy('restaurant_id');

        // Fetch documents per restaurant
        $restaurantIds = $applications->keys();
        $documents = DB::table('restaurant_documents')
            ->whereIn('restaurant_id', $restaurantIds)
            ->get()
            ->groupBy('restaurant_id');

        return $applications->map(function ($apps, $restaurantId) use ($documents) {
            $app = $apps->first();
            $docs = $documents->get($restaurantId, collect());

            return [
                'restaurant_id' => $restaurantId,
                'user_id' => $app->user_id,
                'restaurant_name' => $app->restaurant_name,
                'user_name' => $app->user_name,
                'contact_email' => $app->contact_email,
                'status' => $app->status,
                'documents' => $docs->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'type' => $doc->doc_type,
                        'file_name' => $doc->doc_file,
                        // This uses the stored doc_path (e.g., 'documents/123_image.jpg')
                        'file_path' => $this->getSupabasePublicUrl($doc->doc_path),
                        'uploaded_at' => $doc->created_at,
                    ];
                }),
            ];
        })->values();
    }

  

    public function approve($restaurantId)
    {
        try {
            DB::table('users')
                ->join('restaurant_data', 'users.id', '=', 'restaurant_data.user_id')
                ->where('restaurant_data.id', $restaurantId)
                ->update(['users.status' => 'Approved']);

            return back()->with('success', 'Restaurant approved successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Approval failed: ' . $e->getMessage());
        }
    }


    public function reject($restaurantId)
    {
        try {
            DB::table('users')
                ->join('restaurant_data', 'users.id', '=', 'restaurant_data.user_id')
                ->where('restaurant_data.id', $restaurantId)
                ->update(['users.status' => 'Rejected']);

            return back()->with('success', 'Restaurant rejected');
        } catch (\Exception $e) {
            return back()->with('error', 'Rejection failed: ' . $e->getMessage());
        }
    }
}
