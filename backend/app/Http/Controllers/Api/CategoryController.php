<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CloudinaryUploader;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Throwable;

class CategoryController extends Controller
{
    public function __construct(private readonly CloudinaryUploader $uploader)
    {
    }

    public function index(Request $request)
    {
        try {
            $query = Category::orderBy('sort_order')->orderBy('name');

            if ($request->boolean('active_only', false)) {
                $query->where('is_active', true);
            }

            return $query->get();
        } catch (QueryException $e) {
            report($e);
            return response()->json(['message' => 'Categories unavailable (database not ready).'], 503);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:4096',
            'image_url' => 'nullable|url',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if ($request->hasFile('image')) {
            try {
                $data['image_url'] = $this->uploader->upload($request->file('image'));
            } catch (Throwable $e) {
                report($e);

                return response()->json([
                    'message' => 'Image upload failed: ' . $e->getMessage(),
                ], 422);
            }
        }
        unset($data['image']);

        $category = Category::create($data);

        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:4096',
            'image_url' => 'nullable|url',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if ($request->hasFile('image')) {
            try {
                $data['image_url'] = $this->uploader->upload($request->file('image'));
            } catch (Throwable $e) {
                report($e);

                return response()->json([
                    'message' => 'Image upload failed: ' . $e->getMessage(),
                ], 422);
            }
        }
        unset($data['image']);

        $category->update($data);

        return response()->json($category);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->noContent();
    }
}
