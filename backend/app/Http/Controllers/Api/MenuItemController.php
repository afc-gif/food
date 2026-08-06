<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\MenuItemUpdated;
use App\Models\MenuItem;
use App\Models\PriceHistory;
use App\Services\CloudinaryUploader;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MenuItemController extends Controller
{
    public function __construct(private readonly CloudinaryUploader $uploader)
    {
    }

    public function index(Request $request)
    {
        try {
            $query = MenuItem::with('category')
                ->orderBy('sort_order')
                ->orderBy('name');

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->input('category_id'));
            }

            if ($request->boolean('active_only', false)) {
                $query->where('is_active', true);
            }

            return $query->get();
        } catch (QueryException $e) {
            report($e);
            return response()->json(['message' => 'Menu items unavailable (database not ready).'], 503);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:32|unique:menu_items,barcode',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'is_sold_out' => 'boolean',
            'stock' => 'nullable|integer|min:0',
            'stock_unit' => 'nullable|string|max:50',
            'image_url' => 'nullable|url',
            'image' => 'nullable|image|max:4096',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if ($request->hasFile('image')) {
            try {
                $data['image_url'] = $this->storeImage($request);
            } catch (Throwable $e) {
                report($e);

                return response()->json([
                    'message' => 'Image upload failed: ' . $e->getMessage(),
                ], 422);
            }
        }

        $data['slug'] = Str::slug($data['name'] . '-' . Str::random(6));
        $data['barcode'] = $data['barcode'] ?? MenuItem::generateBarcode();
        if (array_key_exists('stock', $data) && $data['stock'] !== null && (int) $data['stock'] === 0) {
            $data['is_sold_out'] = true;
        }

        $item = MenuItem::create($data);

        PriceHistory::create([
            'menu_item_id' => $item->id,
            'price' => $item->price,
            'changed_by' => $request->user()->email ?? 'system',
        ]);

        $item->load('category');
        $this->broadcastMenuItem($item);

        return response()->json($item, 201);
    }

    public function update(Request $request, MenuItem $menuItem)
    {
        $data = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'sometimes|required|string|max:255',
            'barcode' => 'nullable|string|max:32|unique:menu_items,barcode,' . $menuItem->id,
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'is_sold_out' => 'boolean',
            'stock' => 'nullable|integer|min:0',
            'stock_unit' => 'nullable|string|max:50',
            'image_url' => 'nullable|url',
            'image' => 'nullable|image|max:4096',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        // Ensure every item keeps a barcode even if the incoming payload omitted it.
        if (empty($data['barcode']) && empty($menuItem->barcode)) {
            $data['barcode'] = MenuItem::generateBarcode();
        }

        $priceChanged = array_key_exists('price', $data) && $data['price'] !== null && $data['price'] != $menuItem->price;

        if ($request->hasFile('image')) {
            try {
                $data['image_url'] = $this->storeImage($request);
            } catch (Throwable $e) {
                report($e);

                return response()->json([
                    'message' => 'Image upload failed: ' . $e->getMessage(),
                ], 422);
            }
        }

        if (array_key_exists('stock', $data) && $data['stock'] !== null) {
            if ((int) $data['stock'] === 0) {
                $data['is_sold_out'] = true;
            } elseif ((int) $menuItem->stock === 0 && ! array_key_exists('is_sold_out', $data)) {
                $data['is_sold_out'] = false;
            }
        }

        $menuItem->update($data);

        if ($priceChanged) {
            PriceHistory::create([
                'menu_item_id' => $menuItem->id,
                'price' => $menuItem->price,
                'changed_by' => $request->user()->email ?? 'system',
            ]);
        }

        $menuItem->refresh()->load('category');
        $this->broadcastMenuItem($menuItem);

        return response()->json($menuItem);
    }

    public function regenerateBarcode(MenuItem $menuItem)
    {
        $menuItem->update(['barcode' => MenuItem::generateBarcode()]);

        return response()->json($menuItem->load('category'));
    }

    public function lookup(Request $request)
    {
        $data = $request->validate([
            'barcode' => 'required|string',
        ]);

        try {
            $item = MenuItem::with('category')
                ->where('barcode', $data['barcode'])
                ->first();
        } catch (QueryException $e) {
            report($e);
            return response()->json(['message' => 'Menu lookup unavailable (database not ready).'], 503);
        }

        if (! $item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        if ($item->is_sold_out || $item->stock === 0) {
            return response()->json(['message' => 'Item is sold out'], 409);
        }

        return response()->json($item);
    }

    public function toggleSoldOut(MenuItem $menuItem)
    {
        $menuItem->update(['is_sold_out' => ! $menuItem->is_sold_out]);

        $menuItem->refresh()->load('category');
        $this->broadcastMenuItem($menuItem);

        return response()->json($menuItem);
    }

    public function destroy(MenuItem $menuItem)
    {
        $menuItem->delete();

        return response()->noContent();
    }

    private function storeImage(Request $request): string
    {
        return $this->uploader->upload($request->file('image'));
    }

    private function broadcastMenuItem(MenuItem $item): void
    {
        try {
            broadcast(new MenuItemUpdated($item));
        } catch (Throwable $e) {
            Log::warning('Menu item broadcast failed', [
                'item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
