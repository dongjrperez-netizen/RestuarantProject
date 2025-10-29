<?php

namespace App\Events;

use App\Models\CustomerOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $previousStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(CustomerOrder $order, string $previousStatus)
    {
        $this->order = $order->load(['table', 'orderItems.dish', 'orderItems.variant', 'employee']);
        $this->previousStatus = $previousStatus;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('restaurant.' . $this->order->restaurant_id . '.kitchen'),
            new PrivateChannel('restaurant.' . $this->order->restaurant_id . '.cashier'),
            new PrivateChannel('restaurant.' . $this->order->restaurant_id . '.waiter'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order' => $this->order->toArray(),
            'previous_status' => $this->previousStatus,
            'new_status' => $this->order->status,
            'message' => $this->getStatusMessage(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get status-specific message.
     */
    private function getStatusMessage(): string
    {
        switch ($this->order->status) {
            case 'in_progress':
                return 'Order is now being prepared in kitchen';
            case 'ready':
                return 'Order is ready for serving';
            case 'completed':
                return 'Order has been served to customer';
            case 'paid':
                return 'Order has been paid';
            default:
                return 'Order status updated';
        }
    }
}