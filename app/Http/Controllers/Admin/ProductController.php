<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Exports\ProductSampleExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportProductsRequest;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Imports\ProductsImport;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.products.index', $this->productService->buildIndexViewData($request->query('q', '')));
    }

    public function create(): View
    {
        return view('admin.products.create', $this->productService->buildCreateViewData());
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->productService->create($request);

        return redirect()->route('admin.products.index')
            ->with('success', __('Product created successfully.'));
    }

    public function edit(Product $product): View
    {
        return view('admin.products.edit', $this->productService->buildEditViewData($product));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->update($request, $product);

        return redirect()->route('admin.products.index')
            ->with('success', __('Product updated successfully.'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->delete($product);

        return redirect()->route('admin.products.index')
            ->with('success', __('Product deleted successfully.'));
    }

    public function import(ImportProductsRequest $request): RedirectResponse
    {

        $import = new ProductsImport;
        Excel::import($import, $request->file('file'));

        return redirect()->route('admin.products.index')
            ->with('import_imported', $import->getImported())
            ->with('import_updated', $import->getUpdated())
            ->with('import_errors', $import->getErrors());
    }

    public function sampleDownload(): BinaryFileResponse
    {
        return Excel::download(new ProductSampleExport, 'products_import_sample.xlsx');
    }

    public function reorder(Request $request): JsonResponse
    {
        $orderedIds = array_values(array_filter((array) $request->input('ordered_ids', []), 'is_numeric'));

        if ($orderedIds === []) {
            return response()->json(['success' => false, 'message' => 'No IDs provided.'], 422);
        }

        $page    = max(1, (int) $request->input('page', 1));
        $perPage = max(1, (int) $request->input('per_page', 15));

        $this->productService->reorder($orderedIds, $page, $perPage);

        return response()->json(['success' => true]);
    }
}
