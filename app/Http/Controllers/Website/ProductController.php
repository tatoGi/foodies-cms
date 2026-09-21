<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\Website\WebsiteProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        private readonly WebsiteProductService $productService,
    ) {}

    public function index(): JsonResponse
    {
        $locale = request()->query('locale');
        $data = $this->productService->buildProductListData($locale);

        return response()->json($data);
    }

    public function show(string $slug): JsonResponse
    {
        $data = $this->productService->buildProductData($slug, request()->query('locale'));

        abort_if($data === null, 404);

        return response()->json($data);
    }
}
