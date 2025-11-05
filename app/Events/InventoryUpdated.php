<?php

namespace App\Events;

use App\Models\Ingredients;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InventoryUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ingredient;
    public $action;
    public $previousStock;
    public $newStock;

    /**
     * Create a new event instance.
     */
    public function __construct(Ingredients $ingredient, string $action, float $previousStock = null)
    {
        $this->ingredient = $ingredient;
        $this->action = $action; // 'increased', 'decreased', 'updated'
        $this->previousStock = $previousStock;
        $this->newStock = $ingredient->current_stock;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('restaurant.' . $this->ingredient->restaurant_id . '.inventory'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'inventory.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'ingredient' => [
                'ingredient_id' => $this->ingredient->ingredient_id,
                'ingredient_name' => $this->ingredient->ingredient_name,
                'current_stock' => $this->ingredient->current_stock,
                'packages' => $this->ingredient->packages,
                'base_unit' => $this->ingredient->base_unit,
                'reorder_level' => $this->ingredient->reorder_level,
                'cost_per_unit' => $this->ingredient->cost_per_unit,
            ],
            'action' => $this->action,
            'previous_stock' => $this->previousStock,
            'new_stock' => $this->newStock,
            'message' => $this->getActionMessage(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get action-specific message.
     */
    private function getActionMessage(): string
    {
        $stockChange = $this->newStock - $this->previousStock;
        $changeText = $stockChange > 0 ? '+' . $stockChange : $stockChange;

        switch ($this->action) {
            case 'increased':
                return "{$this->ingredient->ingredient_name} stock increased by {$changeText} {$this->ingredient->base_unit}";
            case 'decreased':
                return "{$this->ingredient->ingredient_name} stock decreased by {$changeText} {$this->ingredient->base_unit}";
            case 'updated':
                return "{$this->ingredient->ingredient_name} stock updated";
            default:
                return 'Inventory updated';
        }
    }
}
