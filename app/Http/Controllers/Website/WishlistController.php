<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['items' => []]);
        }

        $wishlistItems = $user->wishlist()->with('product')->get();

        $items = $wishlistItems->map(function ($item) {
            $product = $item->product;

            return [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->title,
                'price' => $product->price,
                'feature_image' => $product->feature_image,
                'category' => $product->category,
            ];
        });

        return response()->json(['items' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $productId = $request->product_id;

        // Check if already in wishlist
        $exists = $user->wishlist()->where('product_id', $productId)->exists();

        if ($exists) {
            return response()->json(['message' => 'Product already in wishlist']);
        }

        // Add to wishlist
        $user->wishlist()->create(['product_id' => $productId]);

        return response()->json(['message' => 'Product added to wishlist']);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $deleted = $user->wishlist()->where('product_id', $productId)->delete();

        if ($deleted) {
            return response()->json(['message' => 'Product removed from wishlist']);
        }

        return response()->json(['error' => 'Product not found in wishlist'], 404);
    }
}
