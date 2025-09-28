<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function index()
    {
        $pendingApplications = $this->getApplicationsByStatus('Pending');
        $allApplications = $this->getApplicationsByStatus();

        return Inertia::render('Admin/PendingAccounts', [
            'pendingApplications' => $pendingApplications,
            'allApplications' => $allApplications,
        ]);
    }

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

        // Get documents for each restaurant
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
                        'file_path' => asset("storage/{$doc->doc_path}"),
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
            return back()->with('error', 'Approval failed: '.$e->getMessage());
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
            return back()->with('error', 'Rejection failed: '.$e->getMessage());
        }
    }
}
