<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductApiResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * List active products with optional sync filter.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::with('category', 'variants')
            ->where('is_active', true);

        // Sync filter: only return products updated since given timestamp
        if ($request->has('updated_since')) {
            $query->where('updated_at', '>=', $request->input('updated_since'));
        }

        // Category filter
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $products = $query->orderBy('name')->get();

        return ProductApiResource::collection($products);
    }

    /**
     * Store a new product (admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|unique:products,barcode',
            'purchase_price' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => new ProductApiResource($product->load('category', 'variants')),
        ], 201);
    }

    /**
     * Update an existing product (admin only).
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category_id' => 'nullable|uuid|exists:categories,id',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
            'purchase_price' => 'sometimes|numeric|min:0',
            'price' => 'sometimes|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => new ProductApiResource($product->load('category', 'variants')),
        ]);
    }

    /**
     * Soft delete a product (admin only).
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
