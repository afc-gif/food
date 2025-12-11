<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ETAAssigned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing(['items', 'payments', 'creator']);
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('orders')];
    }

    public function broadcastAs(): string
    {
        return 'eta.assigned';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'code' => $this->order->code,
            'kitchen_eta_minutes' => $this->order->kitchen_eta_minutes,
            'kitchen_eta_at' => $this->order->kitchen_eta_at?->toISOString(),
            'customer_name' => $this->order->customer_name,
            'customer_phone' => $this->order->customer_phone,
            'channel' => $this->order->channel,
        ];
    }
}
