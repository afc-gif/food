<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreItem;
use App\Models\StoreAdjustment;
use Illuminate\Http\Request;

class StoreInventoryController extends Controller
{
    // ------------------------------------------------------------------ index
    public function index()
    {
        return StoreItem::orderBy('category')
            ->orderBy('name')
            ->get();
    }

    // ------------------------------------------------------------------ store
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'category'            => 'nullable|string|max:100',
            'quantity'            => 'required|numeric|min:0',
            'unit'                => 'required|string|max:50',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'supplier_notes'      => 'nullable|string',
        ]);

        $item = StoreItem::create($data);

        // Log the initial stock as a restock adjustment
        if ((float) $item->quantity > 0) {
            StoreAdjustment::create([
                'store_item_id'   => $item->id,
                'quantity_change' => $item->quantity,
                'reason'          => 'Initial stock',
                'adjusted_by'     => $request->user()->email ?? 'system',
            ]);
        }

        return response()->json($item, 201);
    }

    // ------------------------------------------------------------------ update
    public function update(Request $request, StoreItem $storeItem)
    {
        $data = $request->validate([
            'name'                => 'sometimes|required|string|max:255',
            'category'            => 'nullable|string|max:100',
            'unit'                => 'sometimes|required|string|max:50',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'supplier_notes'      => 'nullable|string',
        ]);

        $storeItem->update($data);

        return response()->json($storeItem->fresh());
    }

    // ------------------------------------------------------------------ destroy
    public function destroy(StoreItem $storeItem)
    {
        $storeItem->delete();

        return response()->noContent();
    }

    // ------------------------------------------------------------------ adjust
    public function adjust(Request $request, StoreItem $storeItem)
    {
        $data = $request->validate([
            'quantity_change' => 'required|numeric|not_in:0',
            'reason'          => 'nullable|string|max:255',
        ]);

        // Prevent quantity going negative
        $newQty = (float) $storeItem->quantity + (float) $data['quantity_change'];
        if ($newQty < 0) {
            return response()->json([
                'message' => 'Adjustment would result in a negative quantity.',
            ], 422);
        }

        $storeItem->update(['quantity' => $newQty]);

        StoreAdjustment::create([
            'store_item_id'   => $storeItem->id,
            'quantity_change' => $data['quantity_change'],
            'reason'          => $data['reason'] ?? null,
            'adjusted_by'     => $request->user()->email ?? 'system',
        ]);

        return response()->json($storeItem->fresh());
    }

    // ------------------------------------------------------------------ history
    public function history(StoreItem $storeItem)
    {
        $adjustments = $storeItem->adjustments()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json($adjustments);
    }
}
