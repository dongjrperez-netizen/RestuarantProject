<?php

namespace App\Http\Controllers;

use App\Models\CustomerOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SSEController extends Controller
{
    public function kitchenStream()
    {
        $employee = Auth::guard('kitchen')->user();
        if (!$employee) {
            abort(403);
        }

        $restaurantId = $employee->user_id;
        
        // Set unlimited execution time for SSE
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        return response()->stream(function () use ($restaurantId) {
            $lastCheck = now();
            $counter = 0;
            
            while (true) {
                // Send heartbeat every 10 iterations to keep connection alive
                if ($counter % 10 === 0) {
                    echo "data: " . json_encode(['type' => 'heartbeat', 'time' => now()->toISOString()]) . "\n\n";
                    ob_flush();
                    flush();
                }
                
                // Check for all current orders (not just updated ones)
                $orders = CustomerOrder::with(['table', 'orderItems.dish', 'orderItems.variant', 'employee'])
                    ->where('restaurant_id', $restaurantId)
                    ->whereIn('status', ['pending', 'ready', 'completed', 'in_progress'])
                    ->whereHas('orderItems', function ($query) {
                        $query->whereColumn('served_quantity', '<', 'quantity');
                    })
                    ->orderBy('created_at', 'desc')
                    ->limit(20)
                    ->get();

                echo "data: " . json_encode([
                    'type' => 'orders_update',
                    'orders' => $orders->toArray(),
                    'timestamp' => now()->toISOString()
                ]) . "\n\n";
                
                ob_flush();
                flush();

                $counter++;
                sleep(3); // Check every 3 seconds
                
                // Break if connection is closed
                if (connection_aborted()) {
                    break;
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ]);
    }

    public function cashierStream()
    {
        $employee = Auth::guard('cashier')->user();
        if (!$employee) {
            abort(403);
        }

        $restaurantId = $employee->user_id;
        
        // Set unlimited execution time for SSE
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        return response()->stream(function () use ($restaurantId) {
            $counter = 0;
            
            while (true) {
                // Send heartbeat every 10 iterations to keep connection alive
                if ($counter % 10 === 0) {
                    echo "data: " . json_encode(['type' => 'heartbeat', 'time' => now()->toISOString()]) . "\n\n";
                    ob_flush();
                    flush();
                }
                
                // Check for all current orders ready for payment
                $orders = CustomerOrder::with(['table', 'orderItems.dish', 'employee', 'payments', 'reservation'])
                    ->where('restaurant_id', $restaurantId)
                    ->whereIn('status', ['pending', 'ready', 'completed', 'in_progress'])
                    ->orderBy('updated_at', 'desc')
                    ->limit(20)
                    ->get();

                echo "data: " . json_encode([
                    'type' => 'orders_update',
                    'orders' => $orders->toArray(),
                    'timestamp' => now()->toISOString()
                ]) . "\n\n";
                
                ob_flush();
                flush();

                $counter++;
                sleep(3); // Check every 3 seconds
                
                // Break if connection is closed
                if (connection_aborted()) {
                    break;
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ]);
    }
}