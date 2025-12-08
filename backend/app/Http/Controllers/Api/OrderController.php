<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCreated;
use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

    public function store(Request $request)
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
        ]);

        return DB::transaction(function () use ($data, $request) {
            $itemsData = [];
            $subtotal = 0;

            foreach ($data['items'] as $itemInput) {
                $menuItem = MenuItem::findOrFail($itemInput['menu_item_id']);
                if (! $menuItem->is_active) {
                    throw ValidationException::withMessages([
                        'items' => ["{$menuItem->name} is inactive."],
                    ]);
                }
                // Always trust server-side price to prevent client tampering
                $price = $menuItem->price;
                $lineTotal = $price * $itemInput['quantity'];

                if ($menuItem->is_sold_out) {
                    throw ValidationException::withMessages([
                        'items' => ["{$menuItem->name} is sold out."],
                    ]);
                }

                $itemsData[] = [
                    'menu_item_id' => $menuItem->id,
                    'name' => $menuItem->name,
                    'quantity' => $itemInput['quantity'],
                    'unit_price' => $price,
                    'total' => $lineTotal,
                ];

                $subtotal += $lineTotal;
            }

            $discount = $data['discount'] ?? 0;
            $tax = $data['tax'] ?? 0;
            $total = max(0, $subtotal + $tax - $discount);
            $hasPayment = ! empty($data['payment']);

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

            DB::afterCommit(function () use ($order) {
                broadcast(new OrderCreated(
                    $order->fresh(['items', 'payments', 'creator'])
                ));
            });

            return $order->load(['items', 'payments']);
        });
    }

    public function summary()
    {
        $today = Carbon::today();

        $todayOrders = Order::whereDate('created_at', $today)->count();
        $todayRevenue = Order::whereDate('created_at', $today)->sum('total');

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
                'orders' => (int) ($found->orders ?? 0),
                'revenue' => (float) ($found->revenue ?? 0),
            ];
        }

        return [
            'today_orders' => $todayOrders,
            'today_revenue' => (float) $todayRevenue,
            'series' => $series,
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
}
