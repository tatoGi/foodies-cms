<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\MediaFolder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MediaFolderController extends Controller
{
    public function index(): JsonResponse
    {
        $folders = MediaFolder::query()
            ->with('children.children')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'folders' => $this->serializeTree($folders->whereNull('parent_id')->values()),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'integer', Rule::exists('media_folders', 'id')],
        ]);

        $folder = MediaFolder::create([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => trim($validated['name']),
            'slug' => $this->uniqueSlug(trim($validated['name']), $validated['parent_id'] ?? null),
            'sort_order' => $this->nextSortOrder($validated['parent_id'] ?? null),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'folder' => $this->serializeFolder($folder),
            ]);
        }

        return back()->with('success', __('Folder created.'));
    }

    public function update(Request $request, int $folder): JsonResponse|RedirectResponse
    {
        $item = MediaFolder::query()->findOrFail($folder);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $item->update([
            'name' => trim($validated['name']),
            'slug' => $this->uniqueSlug(trim($validated['name']), $item->parent_id, $item->id),
            'sort_order' => isset($validated['sort_order']) ? (int) $validated['sort_order'] : $item->sort_order,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'folder' => $this->serializeFolder($item->fresh()),
            ]);
        }

        return back()->with('success', __('Folder updated.'));
    }

    public function destroy(Request $request, int $folder): JsonResponse|RedirectResponse
    {
        $item = MediaFolder::query()->findOrFail($folder);
        $targetParent = $item->parent_id;

        Media::query()
            ->where('folder_id', $item->id)
            ->update(['folder_id' => $targetParent]);

        MediaFolder::query()
            ->where('parent_id', $item->id)
            ->update(['parent_id' => $targetParent]);

        $item->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('Folder deleted. Contents moved to parent.'));
    }

    private function uniqueSlug(string $name, ?int $parentId, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'folder';
        $slug = $base;
        $counter = 1;

        while (
            MediaFolder::query()
                ->when($ignoreId !== null, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('parent_id', $parentId)
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function nextSortOrder(?int $parentId): int
    {
        $last = (int) MediaFolder::query()
            ->where('parent_id', $parentId)
            ->max('sort_order');

        return $last + 1;
    }

    /**
     * @param  Collection<int, MediaFolder>  $folders
     * @return array<int, array<string, mixed>>
     */
    private function serializeTree(Collection $folders): array
    {
        return $folders
            ->map(function (MediaFolder $folder): array {
                return [
                    ...$this->serializeFolder($folder),
                    'children' => $this->serializeTree($folder->children->sortBy('sort_order')->values()),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeFolder(MediaFolder $folder): array
    {
        return [
            'id' => (int) $folder->id,
            'parent_id' => $folder->parent_id,
            'name' => (string) $folder->name,
            'slug' => (string) $folder->slug,
            'sort_order' => (int) $folder->sort_order,
            'path' => (string) $folder->path,
        ];
    }
}
