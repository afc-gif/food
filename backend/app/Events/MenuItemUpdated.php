<?php

namespace App\Events;

use App\Models\MenuItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MenuItemUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public MenuItem $menuItem)
    {
        $this->menuItem->loadMissing('category');
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('menu-items')];
    }

    public function broadcastAs(): string
    {
        return 'menu-item.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->menuItem->id,
            'name' => $this->menuItem->name,
            'price' => (float) $this->menuItem->price,
            'description' => $this->menuItem->description,
            'image_url' => $this->menuItem->image_url,
            'is_active' => (bool) $this->menuItem->is_active,
            'is_sold_out' => (bool) $this->menuItem->is_sold_out,
            'stock' => $this->menuItem->stock,
            'stock_unit' => $this->menuItem->stock_unit,
            'category' => $this->menuItem->category ? [
                'id' => $this->menuItem->category->id,
                'name' => $this->menuItem->category->name,
            ] : null,
        ];
    }
}
