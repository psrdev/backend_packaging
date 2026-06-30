<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return Product::latest()->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'platform_product_id' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string'],
            'packing_notes' => ['nullable', 'string'],
            'is_fragile' => ['nullable', 'boolean'],
        ]);

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        return $product;
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku,' . $product->id],
            'name' => ['required', 'string', 'max:255'],
            'platform_product_id' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string'],
            'packing_notes' => ['nullable', 'string'],
            'is_fragile' => ['nullable', 'boolean'],
        ]);

        $product->update($data);

        return $product;
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }
}
