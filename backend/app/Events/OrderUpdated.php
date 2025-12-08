<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing(['items', 'payments', 'creator']);
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('orders')];
    }

    public function broadcastAs(): string
    {
        return 'order.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'code' => $this->order->code,
            'status' => $this->order->status,
            'channel' => $this->order->channel,
            'customer_name' => $this->order->customer_name,
            'customer_phone' => $this->order->customer_phone,
            'total' => (float) $this->order->total,
            'paid_at' => $this->order->paid_at?->toISOString(),
            'created_at' => $this->order->created_at?->toISOString(),
            'kitchen_status' => $this->order->kitchen_status,
            'kitchen_eta_minutes' => $this->order->kitchen_eta_minutes,
            'kitchen_eta_at' => $this->order->kitchen_eta_at?->toISOString(),
            'kitchen_sent_at' => $this->order->kitchen_sent_at?->toISOString(),
            'kitchen_note' => $this->order->kitchen_note,
            'items' => $this->order->items->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total' => (float) $item->total,
            ]),
            'payments' => $this->order->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'method' => $payment->method,
                'reference' => $payment->reference,
                'paid_at' => $payment->paid_at?->toISOString(),
            ]),
            'creator' => $this->order->creator ? [
                'id' => $this->order->creator->id,
                'name' => $this->order->creator->name,
                'email' => $this->order->creator->email,
            ] : null,
        ];
    }
}
