<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Events\ETAAssigned;
use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\InventoryAdjustment;
use App\Services\BusinessHours;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items', 'payments', 'creator'])->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->input('channel'));
        }

        if ($request->boolean('all', false)) {
            return $query->get();
        }

        return $query->paginate(25);
    }

    public function show(Order $order)
    {
        return $order->load(['items', 'payments']);
    }

    public function store(Request $request, BusinessHours $businessHours)
    {
        $data = $request->validate([
            'channel' => 'nullable|string|max:50',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:32',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'nullable|numeric|min:0',
            'payment' => 'nullable|array',
            'payment.amount' => 'required_with:payment|numeric|min:0',
            'payment.method' => 'required_with:payment|string|max:50',
            'payment.reference' => 'nullable|string|max:255',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'send_to_kitchen' => 'sometimes|boolean',
            'note' => 'nullable|string|max:500',
            'service' => 'nullable|string|max:100',
            'time' => 'nullable|string|max:100',
        ]);

        if (($data['channel'] ?? 'pos') === 'web') {
            $availability = $businessHours->availability();
            if (! $availability['is_open']) {
                return response()->json([
                    'message' => $availability['message'],
                    'availability' => $availability,
                ], 409);
            }
        }

        return DB::transaction(function () use ($data, $request) {
            $itemsData = [];
            $subtotal = 0;
            $changedMenuItems = collect();
            $requestedItems = collect($data['items'])
                ->groupBy('menu_item_id')
                ->map(fn ($items, $menuItemId) => [
                    'menu_item_id' => (int) $menuItemId,
                    'quantity' => $items->sum('quantity'),
                ])
                ->values();

            $menuItems = MenuItem::whereIn('id', $requestedItems->pluck('menu_item_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($requestedItems as $itemInput) {
                $menuItem = $menuItems->get($itemInput['menu_item_id']);
                if (! $menuItem) {
                    throw ValidationException::withMessages([
                        'items' => ['One or more menu items could not be found.'],
                    ]);
                }

                if (! $menuItem->is_active) {
                    throw ValidationException::withMessages([
                        'items' => ["{$menuItem->name} is inactive."],
                    ]);
                }
                // Always trust server-side price to prevent client tampering
                $price = $menuItem->price;
                $quantity = (int) $itemInput['quantity'];
                $lineTotal = $price * $quantity;

                if ($menuItem->is_sold_out || $menuItem->stock === 0) {
                    throw ValidationException::withMessages([
                        'items' => ["{$menuItem->name} is sold out."],
                    ]);
                }

                if ($menuItem->stock !== null && $menuItem->stock < $quantity) {
                    $unit = $this->formatStockUnit($menuItem->stock, $menuItem->stock_unit);
                    throw ValidationException::withMessages([
                        'items' => ["Only {$menuItem->stock} {$unit} of {$menuItem->name} left."],
                    ]);
                }

                if ($menuItem->stock !== null) {
                    $menuItem->stock -= $quantity;
                    if ($menuItem->stock <= 0) {
                        $menuItem->stock = 0;
                        $menuItem->is_sold_out = true;
                    }
                    $menuItem->save();

                    InventoryAdjustment::create([
                        'menu_item_id' => $menuItem->id,
                        'quantity_change' => -$quantity,
                        'reason' => 'order',
                        'changed_by' => $request->user()->email ?? 'website',
                    ]);

                    $changedMenuItems->push($menuItem->fresh('category'));
                }

                $itemsData[] = [
                    'menu_item_id' => $menuItem->id,
                    'name' => $menuItem->name,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'total' => $lineTotal,
                ];

                $subtotal += $lineTotal;
            }

            $discount = $data['discount'] ?? 0;
            $tax = $data['tax'] ?? 0;
            $total = max(0, $subtotal + $tax - $discount);
            $hasPayment = ! empty($data['payment']);
            $sendToKitchen = array_key_exists('send_to_kitchen', $data)
                ? (bool) $data['send_to_kitchen']
                : true;

            $kitchenNoteParts = [];
            if (! empty($data['service'])) {
                $kitchenNoteParts[] = 'Service: '.$data['service'];
            }
            if (! empty($data['time'])) {
                $kitchenNoteParts[] = 'Time: '.$data['time'];
            }
            if (! empty($data['note'])) {
                $kitchenNoteParts[] = 'Note: '.$data['note'];
            }
            $kitchenNote = $kitchenNoteParts ? implode(' | ', $kitchenNoteParts) : null;

            $order = Order::create([
                'channel' => $data['channel'] ?? 'pos',
                'created_by' => $request->user()?->id,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'status' => $hasPayment ? 'paid' : 'pending',
                'paid_at' => $hasPayment ? now() : null,
                'kitchen_status' => $sendToKitchen ? 'queued' : 'pending',
                'kitchen_sent_at' => $sendToKitchen ? now() : null,
                'kitchen_note' => $kitchenNote,
            ]);

            foreach ($itemsData as $item) {
                $order->items()->create($item);
            }

            if ($hasPayment) {
                $order->payments()->create([
                    // Align recorded payment with computed total to prevent under/over collection discrepancies
                    'amount' => $total,
                    'method' => $data['payment']['method'],
                    'reference' => $data['payment']['reference'] ?? null,
                    'paid_at' => now(),
                ]);
            }

            DB::afterCommit(function () use ($order, $changedMenuItems) {
                $this->broadcastOrderChange($order, true);
                $changedMenuItems->each(fn (MenuItem $item) => $this->broadcastMenuItemChange($item));
            });

            return $order->load(['items', 'payments']);
        });
    }

    public function sendToKitchen(Request $request, Order $order)
    {
        $data = $request->validate([
            'note' => 'nullable|string|max:500',
            'eta_minutes' => 'nullable|integer|min:1|max:240',
        ]);

        $etaAt = $this->resolveEta($data['eta_minutes'] ?? null);

        $order->fill([
            'kitchen_status' => 'queued',
            'kitchen_sent_at' => $order->kitchen_sent_at ?? now(),
            'kitchen_note' => $data['note'] ?? null,
            'kitchen_eta_minutes' => $data['eta_minutes'] ?? null,
            'kitchen_eta_at' => $etaAt,
        ])->save();

        DB::afterCommit(fn () => $this->broadcastOrderChange($order));

        return $order->fresh(['items', 'payments', 'creator']);
    }

    public function updateKitchenStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'kitchen_status' => 'required|string|in:pending,queued,prepping,ready,served',
            'eta_minutes' => 'nullable|integer|min:1|max:240',
            'note' => 'nullable|string|max:500',
        ]);

        $etaAt = $this->resolveEta($data['eta_minutes'] ?? $order->kitchen_eta_minutes);

        $hadEta = $order->kitchen_eta_minutes !== null;
        $hasEta = isset($data['eta_minutes']) && $data['eta_minutes'] !== null;

        $order->fill([
            'kitchen_status' => $data['kitchen_status'],
            'kitchen_eta_minutes' => $data['eta_minutes'] ?? $order->kitchen_eta_minutes,
            'kitchen_eta_at' => $etaAt,
            'kitchen_note' => $data['note'] ?? $order->kitchen_note,
            'kitchen_sent_at' => $order->kitchen_sent_at ?? now(),
        ])->save();

        DB::afterCommit(function () use ($order, $hadEta, $hasEta) {
            $this->broadcastOrderChange($order);
            // Broadcast ETA assignment only if ETA was just set
            if (!$hadEta && $hasEta) {
                broadcast(new ETAAssigned($order))->toOthers();
            }
        });

        return $order->fresh(['items', 'payments', 'creator']);
    }

    public function approve(Request $request, Order $order)
    {
        $data = $request->validate([
            'note' => 'nullable|string|max:500',
            'send_to_kitchen' => 'sometimes|boolean',
        ]);

        $sendToKitchen = array_key_exists('send_to_kitchen', $data)
            ? (bool) $data['send_to_kitchen']
            : true;

        $order->fill([
            'status' => 'paid',
            'paid_at' => $order->paid_at ?? now(),
            'kitchen_status' => $sendToKitchen ? 'queued' : 'pending',
            'kitchen_sent_at' => $sendToKitchen ? ($order->kitchen_sent_at ?? now()) : $order->kitchen_sent_at,
            'kitchen_note' => $data['note'] ?? $order->kitchen_note,
        ])->save();

        DB::afterCommit(fn () => $this->broadcastOrderChange($order));

        return $order->fresh(['items', 'payments', 'creator']);
    }

    public function destroy(Order $order)
    {
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be deleted before approval.',
            ], 409);
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted.']);
    }

    private function formatStockUnit(int $quantity, ?string $unit): string
    {
        $unit = trim((string) $unit);
        if ($unit === '') {
            return 'left';
        }

        if ($quantity === 1) {
            return rtrim($unit, 's');
        }

        return str_ends_with($unit, 's') ? $unit : $unit . 's';
    }

    private function broadcastMenuItemChange(MenuItem $item): void
    {
        try {
            broadcast(new \App\Events\MenuItemUpdated($item));
        } catch (\Throwable $e) {
            Log::warning('Menu item broadcast failed after order stock update', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function summary()
    {
        $today = Carbon::today();
        $weekStart = Carbon::today()->startOfWeek();
        $monthStart = Carbon::today()->startOfMonth();

        $todayOrders = Order::whereDate('created_at', $today)->count();
        $todayRevenue = Order::whereDate('created_at', $today)->sum('total');
        $weekOrders = Order::where('created_at', '>=', $weekStart)->count();
        $weekRevenue = Order::where('created_at', '>=', $weekStart)->sum('total');
        $monthOrders = Order::where('created_at', '>=', $monthStart)->count();
        $monthRevenue = Order::where('created_at', '>=', $monthStart)->sum('total');
        $totalOrders = Order::count();
        $totalRevenue = Order::sum('total');

        $since = Carbon::today()->subDays(6);
        $seriesRaw = Order::selectRaw('DATE(created_at) as day, COUNT(*) as orders, SUM(total) as revenue')
            ->whereDate('created_at', '>=', $since)
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $series = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $since->copy()->addDays($i)->format('Y-m-d');
            $found = $seriesRaw->firstWhere('day', $day);
            $series[] = [
                'day' => $day,
                'label' => $since->copy()->addDays($i)->format('M j'),
                'orders' => (int) ($found->orders ?? 0),
                'revenue' => (float) ($found->revenue ?? 0),
            ];
        }

        $weeklyRaw = Order::query()
            ->where('created_at', '>=', Carbon::today()->subWeeks(7)->startOfWeek())
            ->get(['created_at', 'total'])
            ->groupBy(fn (Order $order) => $order->created_at->copy()->startOfWeek()->format('Y-m-d'));
        $weekly = [];
        for ($i = 7; $i >= 0; $i--) {
            $week = Carbon::today()->subWeeks($i)->startOfWeek();
            $key = $week->format('Y-m-d');
            $orders = $weeklyRaw->get($key, collect());
            $weekly[] = [
                'week' => $key,
                'label' => $week->format('M j') . ' - ' . $week->copy()->endOfWeek()->format('M j'),
                'orders' => $orders->count(),
                'revenue' => (float) $orders->sum('total'),
            ];
        }

        $monthlyRaw = Order::query()
            ->where('created_at', '>=', Carbon::today()->subMonths(11)->startOfMonth())
            ->get(['created_at', 'total'])
            ->groupBy(fn (Order $order) => $order->created_at->format('Y-m'));
        $monthly = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::today()->subMonths($i)->startOfMonth();
            $key = $month->format('Y-m');
            $orders = $monthlyRaw->get($key, collect());
            $monthly[] = [
                'month' => $key,
                'label' => $month->format('M Y'),
                'orders' => $orders->count(),
                'revenue' => (float) $orders->sum('total'),
            ];
        }

        return [
            'today_orders' => $todayOrders,
            'today_revenue' => (float) $todayRevenue,
            'week_orders' => $weekOrders,
            'week_revenue' => (float) $weekRevenue,
            'month_orders' => $monthOrders,
            'month_revenue' => (float) $monthRevenue,
            'total_orders' => $totalOrders,
            'total_revenue' => (float) $totalRevenue,
            'series' => $series,
            'weekly' => $weekly,
            'monthly' => $monthly,
        ];
    }

    public function purge()
    {
        DB::transaction(function () {
            Payment::query()->delete();
            OrderItem::query()->delete();
            Order::query()->delete();
        });

        return response()->json(['message' => 'All orders cleared.']);
    }

    public function export(Request $request): StreamedResponse
    {
        $range = $request->get('range', 'monthly');
        $from = match ($range) {
            'weekly' => Carbon::today()->subDays(6),
            default => Carbon::today()->subDays(29),
        };

        $orders = Order::with(['items', 'payments', 'creator'])
            ->whereDate('created_at', '>=', $from)
            ->orderByDesc('created_at')
            ->get();

        $filename = 'orders-' . $range . '-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Code',
                'Status',
                'Total',
                'Channel',
                'Customer Name',
                'Customer Phone',
                'Seller',
                'Items Count',
                'Created At',
            ]);

            foreach ($orders as $order) {
                fputcsv($out, [
                    $order->code,
                    $order->status,
                    $order->total,
                    $order->channel,
                    $order->customer_name,
                    $order->customer_phone,
                    $order->creator->name ?? '',
                    $order->items->count(),
                    $order->created_at,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    protected function resolveEta(?int $minutes): ?Carbon
    {
        if (! $minutes) {
            return null;
        }

        return Carbon::now()->addMinutes($minutes);
    }

    protected function broadcastOrderChange(Order $order, bool $isNew = false): void
    {
        $order->loadMissing(['items', 'payments', 'creator']);
        try {
            if ($isNew) {
                broadcast(new OrderCreated($order))->toOthers();
            }
            broadcast(new OrderUpdated($order))->toOthers();
        } catch (\Throwable $e) {
            Log::warning('Order broadcast failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
