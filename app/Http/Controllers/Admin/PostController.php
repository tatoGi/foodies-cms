<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PostController extends Controller
{
    public function __construct(
        private readonly PostService $postService,
    ) {}

    public function index(): View
    {
        return view('admin.posts.index', $this->postService->buildIndexViewData());
    }

    public function create(): View
    {
        return view('admin.posts.create', $this->postService->buildCreateViewData());
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $this->postService->create($request);

        return redirect()->route('admin.posts.index')
            ->with('success', __('Post created successfully.'));
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.edit', $this->postService->buildEditViewData($post));
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $this->postService->update($request, $post);

        return redirect()->route('admin.posts.index')
            ->with('success', __('Post updated successfully.'));
    }

    public function destroy(Post $post): RedirectResponse
    {
        $this->postService->delete($post);

        return redirect()->route('admin.posts.index')
            ->with('success', __('Post deleted successfully.'));
    }

    public function reorder(Request $request): JsonResponse
    {
        $orderedIds = array_values(array_filter((array) $request->input('ordered_ids', []), 'is_numeric'));

        if ($orderedIds === []) {
            return response()->json(['success' => false, 'message' => 'No IDs provided.'], 422);
        }

        $this->postService->reorder($orderedIds);

        return response()->json(['success' => true]);
    }
}
