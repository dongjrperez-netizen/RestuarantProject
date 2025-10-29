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

class OrderReadyToServe implements ShouldBroadcast
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
            new PrivateChannel('restaurant.' . $this->order->restaurant_id . '.waiter'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.ready.to.serve';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order' => $this->order->toArray(),
            'message' => 'Order ' . $this->order->order_number . ' is ready to serve!',
            'table' => [
                'id' => $this->order->table->table_id ?? null,
                'number' => $this->order->table->table_number ?? 'N/A',
                'name' => $this->order->table->table_name ?? 'N/A',
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
