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

class OrderServed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    /**
     * Create a new event instance.
     */
    public function __construct(CustomerOrder $order)
    {
        $this->order = $order->load(['table', 'orderItems.dish', 'orderItems.variant', 'employee']);
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
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.served';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // Check if all items are fully served
        $allItemsServed = $this->order->orderItems->every(function ($item) {
            return $item->served_quantity >= $item->quantity;
        });

        return [
            'order' => $this->order->toArray(),
            'all_items_served' => $allItemsServed,
            'message' => $allItemsServed 
                ? 'Order has been fully served and is ready for payment' 
                : 'Order items have been served',
            'timestamp' => now()->toISOString(),
        ];
    }
}