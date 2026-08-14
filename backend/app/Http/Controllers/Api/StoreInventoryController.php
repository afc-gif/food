<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StoreAdjustment;
use App\Models\StoreCategory;
use App\Models\StoreItem;
use Illuminate\Http\Request;

class StoreInventoryController extends Controller
{
    // ═══════════════════════════════════════════════
    //  CATEGORIES
    // ═══════════════════════════════════════════════

    public function categories()
    {
        return StoreCategory::orderBy('sort_order')->orderBy('name')->get();
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100|unique:store_categories,name',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        return response()->json(StoreCategory::create($data), 201);
    }

    public function updateCategory(Request $request, StoreCategory $storeCategory)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100|unique:store_categories,name,' . $storeCategory->id,
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $storeCategory->update($data);

        return response()->json($storeCategory->fresh());
    }

    public function destroyCategory(StoreCategory $storeCategory)
    {
        // Null-out items in this category before deleting (nullOnDelete handles FK)
        $storeCategory->delete();

        return response()->noContent();
    }

    // ═══════════════════════════════════════════════
    //  ITEMS
    // ═══════════════════════════════════════════════

    public function index()
    {
        return StoreItem::with('category')
            ->orderBy('store_category_id')
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'store_category_id'   => 'nullable|exists:store_categories,id',
            'quantity'            => 'required|numeric|min:0',
            'unit'                => 'required|string|max:50',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'supplier_notes'      => 'nullable|string',
        ]);

        $item = StoreItem::create($data);

        // Log initial stock
        if ((float) $item->quantity > 0) {
            StoreAdjustment::create([
                'store_item_id'   => $item->id,
                'quantity_change' => $item->quantity,
                'reason'          => 'Initial stock',
                'adjusted_by'     => $request->user()->email ?? 'system',
            ]);
        }

        return response()->json($item->load('category'), 201);
    }

    public function update(Request $request, StoreItem $storeItem)
    {
        $data = $request->validate([
            'name'                => 'sometimes|required|string|max:255',
            'store_category_id'   => 'nullable|exists:store_categories,id',
            'unit'                => 'sometimes|required|string|max:50',
            'low_stock_threshold' => 'nullable|numeric|min:0',
            'supplier_notes'      => 'nullable|string',
        ]);

        $storeItem->update($data);

        return response()->json($storeItem->fresh()->load('category'));
    }

    public function destroy(StoreItem $storeItem)
    {
        $storeItem->delete();

        return response()->noContent();
    }

    // ═══════════════════════════════════════════════
    //  ADJUST STOCK
    // ═══════════════════════════════════════════════

    public function adjust(Request $request, StoreItem $storeItem)
    {
        $data = $request->validate([
            'quantity_change' => 'required|numeric|not_in:0',
            'reason'          => 'nullable|string|max:255',
        ]);

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

        return response()->json($storeItem->fresh()->load('category'));
    }

    // ═══════════════════════════════════════════════
    //  HISTORY
    // ═══════════════════════════════════════════════

    public function history(StoreItem $storeItem)
    {
        return response()->json(
            $storeItem->adjustments()->orderByDesc('created_at')->limit(50)->get()
        );
    }
}
