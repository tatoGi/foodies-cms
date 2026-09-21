<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderReelRequest;
use App\Http\Requests\Admin\StoreReelRequest;
use App\Http\Requests\Admin\UpdateReelRequest;
use App\Models\Reel;
use App\Services\ReelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReelController extends Controller
{
    public function __construct(
        private readonly ReelService $reelService,
    ) {}

    public function index(): View
    {
        return view('admin.reels.index', $this->reelService->buildIndexViewData());
    }

    public function create(): View
    {
        return view('admin.reels.create', $this->reelService->buildCreateViewData());
    }

    public function store(StoreReelRequest $request): RedirectResponse
    {
        $this->reelService->create($request->validated());

        return redirect()->route('admin.reels.index')
            ->with('success', __('Reel created successfully.'));
    }

    public function edit(Reel $reel): View
    {
        return view('admin.reels.edit', $this->reelService->buildEditViewData($reel));
    }

    public function update(UpdateReelRequest $request, Reel $reel): RedirectResponse
    {
        $this->reelService->update($reel, $request->validated());

        return redirect()->route('admin.reels.index')
            ->with('success', __('Reel updated successfully.'));
    }

    public function destroy(Reel $reel): RedirectResponse
    {
        $this->reelService->delete($reel);

        return redirect()->route('admin.reels.index')
            ->with('success', __('Reel deleted.'));
    }

    public function reorder(ReorderReelRequest $request): JsonResponse
    {
        $this->reelService->reorder($request->validated('ordered_ids'));

        return response()->json(['success' => true]);
    }
}
