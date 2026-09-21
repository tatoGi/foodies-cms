<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderPageRequest;
use App\Http\Requests\Admin\StorePageRequest;
use App\Http\Requests\Admin\UpdatePageRequest;
use App\Models\Page;
use App\Services\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private readonly PageService $pageService,
    ) {}

    public function index(): View
    {
        return view('admin.pages.index', $this->pageService->buildIndexViewData());
    }

    public function create(): View
    {
        return view('admin.pages.create', $this->pageService->buildCreateViewData());
    }

    public function store(StorePageRequest $request): RedirectResponse
    {
        $this->pageService->create($request);

        return redirect()->route('admin.pages.index')
            ->with('success', __('Page created successfully.'));
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.edit', $this->pageService->buildEditViewData($page));
    }

    public function update(UpdatePageRequest $request, Page $page): RedirectResponse
    {
        $this->pageService->update($request, $page);

        return redirect()->route('admin.pages.index')
            ->with('success', __('Page updated successfully.'));
    }

    public function destroy(Page $page): RedirectResponse
    {
        $this->pageService->delete($page);

        return redirect()->route('admin.pages.index')
            ->with('success', __('Page deleted successfully.'));
    }

    public function reorder(ReorderPageRequest $request): JsonResponse
    {
        $tree = $request->validated('tree');
        if (is_array($tree) && $tree !== []) {
            $this->pageService->reorderTree($tree);

            return response()->json(['success' => true]);
        }

        $orderedIds = array_values(array_filter((array) $request->validated('ordered_ids', []), 'is_numeric'));
        if ($orderedIds !== []) {
            $this->pageService->reorder($orderedIds);
        }

        return response()->json(['success' => true]);
    }
}
