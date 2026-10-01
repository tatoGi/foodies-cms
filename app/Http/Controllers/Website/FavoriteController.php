<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Services\Website\WebsiteMenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FavoriteController extends Controller
{
    public function __construct(
        private readonly WebsiteMenuService $menu,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $locale = (string) $request->query('locale', 'ka');

        $products = Product::query()
            ->whereIn('id', $user->wishlist()->pluck('product_id'))
            ->where('published', true)
            ->where('is_active', true)
            ->with(['translations', 'addons', 'ingredients'])
            ->get();

        return response()->json([
            'items' => $products->map(fn (Product $product): array => $this->menu->productCard($product, $locale))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('published', true)->where('is_active', true)],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->wishlist()->firstOrCreate(['product_id' => (int) $data['product_id']]);

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->wishlist()->where('product_id', $productId)->delete();

        return response()->json(['success' => true]);
    }
}
