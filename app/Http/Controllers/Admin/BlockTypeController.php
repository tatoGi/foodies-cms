<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlockTypeRequest;
use App\Http\Requests\Admin\UpdateBlockTypeRequest;
use App\Services\BlockTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BlockTypeController extends Controller
{
    public function __construct(
        private readonly BlockTypeService $blockTypeService,
    ) {}

    public function index(): View
    {
        return view('admin.blocks.index', $this->blockTypeService->indexViewData());
    }

    public function create(): View
    {
        return view('admin.blocks.create', $this->blockTypeService->createViewData());
    }

    public function store(StoreBlockTypeRequest $request): RedirectResponse
    {
        $this->blockTypeService->create($request->validated());

        return redirect()
            ->route('admin.blocks.index')
            ->with('success', __('Block type created successfully.'));
    }

    public function edit(string $id): View
    {
        return view('admin.blocks.edit', $this->blockTypeService->editViewData($id));
    }

    public function update(UpdateBlockTypeRequest $request, string $id): RedirectResponse
    {
        $this->blockTypeService->update($id, $request->validated());

        return redirect()
            ->route('admin.blocks.index')
            ->with('success', __('Block type updated successfully.'));
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->blockTypeService->delete($id);

        return redirect()
            ->route('admin.blocks.index')
            ->with('success', __('Block type deleted successfully.'));
    }
}
