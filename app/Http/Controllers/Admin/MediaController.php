<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Services\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $uploadService,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $type = $request->string('type')->toString();
        $search = trim($request->string('q')->toString());
        $folderId = $request->filled('folder_id') ? (int) $request->input('folder_id') : null;
        $view = in_array($request->string('view')->toString(), ['grid', 'list'], true)
            ? $request->string('view')->toString()
            : 'grid';

        $query = Media::query()
            ->with(['folder', 'user'])
            ->withCount('links');

        if ($status === 'trashed') {
            $query->onlyTrashed();
        } elseif ($status === 'all') {
            $query->withTrashed();
        }

        if ($type !== '' && $type !== 'all') {
            $query->ofType($type);
        }

        if ($folderId !== null) {
            $query->inFolder($folderId);
        }

        $query->search($search);

        $media = $query
            ->orderByDesc('created_at')
            ->paginate(24)
            ->withQueryString();

        $folders = MediaFolder::query()
            ->with(['children.children'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $currentFolder = $folderId !== null
            ? MediaFolder::query()->find($folderId)
            : null;

        return view('admin.media.index', [
            'media' => $media,
            'folders' => $this->buildFolderTree($folders),
            'flatFolders' => $folders,
            'currentFolder' => $currentFolder,
            'breadcrumbs' => $this->folderBreadcrumbs($currentFolder),
            'status' => $status === '' ? 'active' : $status,
            'type' => $type === '' ? 'all' : $type,
            'search' => $search,
            'currentView' => $view,
            'chunkThreshold' => (int) config('media.chunk_threshold', 2 * 1024 * 1024),
            'allowedExtensions' => collect((array) config('media.allowed_types', []))->flatten()->values()->all(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $folderId = $request->filled('folder_id') ? (int) $request->input('folder_id') : null;
        $sourceContext = trim((string) $request->input('source_context', ''));
        if ($folderId === null && $sourceContext !== '') {
            $folderId = $this->resolveSourceFolderId($sourceContext);
        }

        $userId = optional(auth('admin')->user())->id;
        $options = [
            'folder_id' => $folderId,
            'user_id' => $userId,
            'title' => $request->input('title'),
            'alt_text' => $request->input('alt_text'),
            'description' => $request->input('description'),
        ];

        if ($request->boolean('is_chunked')) {
            $validated = $request->validate([
                'upload_id' => ['required', 'string', 'max:100'],
                'chunk_index' => ['required', 'integer', 'min:0'],
                'total_chunks' => ['required', 'integer', 'min:1'],
                'original_name' => ['required', 'string', 'max:255'],
                'file' => ['required', 'file'],
            ]);
        
            try {
                $result = $this->uploadService->handleChunkUpload([
                    'upload_id' => $validated['upload_id'],
                    'chunk_index' => (int) $validated['chunk_index'],
                    'total_chunks' => (int) $validated['total_chunks'],
                    'original_name' => $validated['original_name'],
                    'file' => $request->file('file'),
                    'options' => $options,
                ]);
            } catch (\InvalidArgumentException $e) {
                $maxImageMb = (int) config('media.max_image_upload_size', 5 * 1024 * 1024) / 1024 / 1024;
                dd($maxImageMb);
                $maxFileMb = (int) config('media.max_upload_size', 10 * 1024 * 1024) / 1024 / 1024;
                $allowedExtensions = collect((array) config('media.allowed_types', []))->flatten()->implode(', ');

                $message = match (true) {
                    str_contains($e->getMessage(), 'not allowed') =>
                        "ფაილის ტიპი დაუშვებელია. დასაშვები ფორმატებია: {$allowedExtensions}.",
                    str_contains($e->getMessage(), 'size limit') =>
                        "ფაილი ზომით გადიდებულია. სურათებისთვის მაქსიმუმი {$maxImageMb}MB, სხვა ფაილებისთვის {$maxFileMb}MB.",
                    default => $e->getMessage(),
                };

                return response()->json(['success' => false, 'message' => $message], 422);
            } catch (\Throwable) {
                return response()->json([
                    'success' => false,
                    'message' => 'ფაილის ატვირთვა ვერ მოხერხდა. სცადეთ თავიდან ან დაუკავშირდით ადმინისტრატორს.',
                ], 500);
            }

            if (! $result['completed']) {
                return response()->json([
                    'success' => true,
                    'completed' => false,
                ]);
            }

            /** @var Media $uploaded */
            $uploaded = $result['media'];

            return response()->json([
                'success' => true,
                'completed' => true,
                'media' => $this->serializeMedia($uploaded),
            ]);
        }

        if ($request->filled('upload_url')) {
            $validated = $request->validate([
                'upload_url' => ['required', 'url', 'max:2000'],
                'folder_id' => ['nullable', 'integer', Rule::exists('media_folders', 'id')],
            ]);

            $uploaded = $this->uploadService->uploadFromUrl((string) $validated['upload_url'], $options);

            return response()->json([
                'success' => true,
                'media' => [$this->serializeMedia($uploaded)],
            ]);
        }

        $maxImageMb = (int) config('media.max_image_upload_size', 5 * 1024 * 1024) / 1024 / 1024;
        $maxFileMb = (int) config('media.max_upload_size', 10 * 1024 * 1024) / 1024 / 1024;
        $allowedExtensions = collect((array) config('media.allowed_types', []))->flatten()->implode(', ');

        $request->validate([
            'folder_id' => ['nullable', 'integer', Rule::exists('media_folders', 'id')],
            'files' => ['nullable', 'array'],
            'files.*' => ['file'],
            'file' => ['nullable', 'file'],
        ], [
            'files.*.file' => "ფაილი ვერ აიტვირთა. დარწმუნდით, რომ ფაილი დაზიანებული არ არის.",
            'file.file' => "ფაილი ვერ აიტვირთა. დარწმუნდით, რომ ფაილი დაზიანებული არ არის.",
        ]);

        $files = [];
        if ($request->hasFile('files')) {
            $files = array_merge($files, (array) $request->file('files'));
        }

        if ($request->hasFile('file')) {
            $files[] = $request->file('file');
        }

        if ($files === []) {
            return response()->json([
                'success' => false,
                'message' => 'ფაილი არ არის მიბმული. გთხოვთ აირჩიოთ ფაილი.',
            ], 422);
        }

        try {
            $uploadedItems = $this->uploadService->uploadFiles($files, $options)
                ->map(fn (Media $item): array => $this->serializeMedia($item))
                ->values()
                ->all();
        } catch (\InvalidArgumentException $e) {
            $message = match (true) {
                str_contains($e->getMessage(), 'not allowed') =>
                    "ფაილის ტიპი დაუშვებელია. დასაშვები ფორმატებია: {$allowedExtensions}.",
                str_contains($e->getMessage(), 'size limit') =>
                    "ფაილი ზომით გადიდებულია. სურათებისთვის მაქსიმუმი {$maxImageMb}MB, სხვა ფაილებისთვის {$maxFileMb}MB.",
                default => $e->getMessage(),
            };

            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'ფაილის ატვირთვა ვერ მოხერხდა. სცადეთ თავიდან ან დაუკავშირდით ადმინისტრატორს.',
            ], 500);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'media' => $uploadedItems,
            ]);
        }

        return back()->with('success', __('Media uploaded successfully.'));
    }

    public function upload(Request $request): JsonResponse|RedirectResponse
    {
        return $this->store($request);
    }

    public function show(Request $request, int $media): View|JsonResponse
    {
        $item = Media::withTrashed()
            ->with(['folder', 'user'])
            ->findOrFail($media);

        if ($request->expectsJson()) {
            return response()->json([
                'media' => $this->serializeMedia($item, true),
                'usage' => $item->links()
                    ->latest('created_at')
                    ->take(50)
                    ->get()
                    ->map(static fn ($link): array => [
                        'mediable_type' => class_basename((string) $link->mediable_type),
                        'mediable_id' => (int) $link->mediable_id,
                        'collection' => (string) $link->collection,
                        'created_at' => optional($link->created_at)?->toDateTimeString(),
                    ])
                    ->all(),
            ]);
        }

        return view('admin.media.show', [
            'media' => $item,
        ]);
    }

    public function update(Request $request, int $media): JsonResponse|RedirectResponse
    {
        $item = Media::withTrashed()->findOrFail($media);

        $validated = $request->validate([
            'folder_id' => ['nullable', 'integer', Rule::exists('media_folders', 'id')],
            'title' => ['nullable'],
            'alt_text' => ['nullable'],
            'description' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
        $normalizedTitle = $this->normalizeTranslatableInput($validated['title'] ?? null);
        $normalizedAltText = $this->normalizeTranslatableInput($validated['alt_text'] ?? null);
        $normalizedDescription = $this->normalizeTranslatableInput($validated['description'] ?? null);

        $item->update([
            'folder_id' => $validated['folder_id'] ?? null,
            'title' => $normalizedTitle,
            'alt_text' => $normalizedAltText,
            'description' => $normalizedDescription,
            'alt' => $normalizedAltText !== null ? (reset($normalizedAltText) ?: null) : null,
            'sort_order' => isset($validated['sort_order']) ? (int) $validated['sort_order'] : $item->sort_order,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'media' => $this->serializeMedia($item->fresh()),
            ]);
        }

        return back()->with('success', __('Media item updated.'));
    }

    public function destroy(int $media): JsonResponse|RedirectResponse
    {
        $item = Media::query()->findOrFail($media);
        $item->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('Media moved to trash.'));
    }

    public function forceDestroy(int $media): JsonResponse|RedirectResponse
    {
        $item = Media::withTrashed()->findOrFail($media);
        $this->removeStoredFiles($item);
        $item->forceDelete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('Media permanently deleted.'));
    }

    public function restore(int $media): JsonResponse|RedirectResponse
    {
        $item = Media::withTrashed()->findOrFail($media);
        $item->restore();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('Media restored successfully.'));
    }

    public function download(Request $request, int $media)
    {
        $item = Media::withTrashed()->findOrFail($media);
        $conversion = trim((string) $request->query('conversion', ''));

        $path = $conversion !== '' ? $item->getConversionPath($conversion) : (string) $item->path;
        if ($path === null || $path === '') {
            abort(404);
        }

        $disk = Storage::disk((string) $item->disk);
        if (! $disk->exists($path)) {
            abort(404);
        }

        $downloadName = $conversion !== ''
            ? pathinfo((string) $item->filename, PATHINFO_FILENAME).'-'.$conversion.'.'.pathinfo($path, PATHINFO_EXTENSION)
            : (string) $item->filename;

        return $disk->download($path, $downloadName);
    }

    public function bulkDestroy(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', Rule::exists('media', 'id')],
            'mode' => ['nullable', Rule::in(['soft', 'force'])],
        ]);

        $ids = collect($validated['ids'])->map(static fn ($id): int => (int) $id)->unique()->values();
        $mode = (string) ($validated['mode'] ?? 'soft');

        if ($mode === 'force') {
            $items = Media::withTrashed()->whereIn('id', $ids)->get();
            foreach ($items as $item) {
                $this->removeStoredFiles($item);
                $item->forceDelete();
            }
        } else {
            Media::query()->whereIn('id', $ids)->delete();
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('Bulk media delete completed.'));
    }

    public function bulkMove(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', Rule::exists('media', 'id')],
            'folder_id' => ['nullable', 'integer', Rule::exists('media_folders', 'id')],
        ]);

        $ids = collect($validated['ids'])->map(static fn ($id): int => (int) $id)->unique()->values();

        Media::query()
            ->whereIn('id', $ids)
            ->update(['folder_id' => $validated['folder_id'] ?? null]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('Selected media moved.'));
    }

    public function bulkAction(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['delete', 'force_delete', 'restore', 'move'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'folder_id' => ['nullable', 'integer', Rule::exists('media_folders', 'id')],
        ]);

        $ids = collect($validated['ids'])->map(static fn ($id): int => (int) $id)->unique()->values()->all();
        $action = (string) $validated['action'];

        if ($action === 'move') {
            $request->merge(['ids' => $ids, 'folder_id' => $validated['folder_id'] ?? null]);

            return $this->bulkMove($request);
        }

        if ($action === 'restore') {
            Media::withTrashed()->whereIn('id', $ids)->restore();
        } elseif ($action === 'force_delete') {
            $items = Media::withTrashed()->whereIn('id', $ids)->get();
            foreach ($items as $item) {
                $this->removeStoredFiles($item);
                $item->forceDelete();
            }
        } else {
            Media::query()->whereIn('id', $ids)->delete();
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', __('Bulk action completed.'));
    }

    public function browse(Request $request): JsonResponse
    {
        $query = Media::query();

        if ($request->filled('folder_id')) {
            $query->inFolder((int) $request->input('folder_id'));
        }

        if ($request->filled('type') && $request->string('type')->toString() !== 'all') {
            $query->ofType($request->string('type')->toString());
        }

        $query->search($request->string('q')->toString());

        $media = $query
            ->orderByDesc('created_at')
            ->paginate(24);

        return response()->json([
            'data' => $media->getCollection()->map(fn (Media $item): array => $this->serializeMedia($item))->values(),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->merge(['q' => $request->string('q')->toString()]);

        return $this->browse($request);
    }

    /**
     * @param  Collection<int, MediaFolder>  $folders
     * @return Collection<int, MediaFolder>
     */
    private function buildFolderTree(Collection $folders): Collection
    {
        return $folders
            ->whereNull('parent_id')
            ->values();
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    private function folderBreadcrumbs(?MediaFolder $folder): array
    {
        if ($folder === null) {
            return [];
        }

        $trail = [];
        $node = $folder;

        while ($node !== null) {
            $trail[] = [
                'id' => (int) $node->id,
                'name' => (string) $node->name,
            ];
            $node = $node->parent;
        }

        return array_reverse($trail);
    }

    /**
     * @return array<string,mixed>
     */
    private function serializeMedia(Media $item, bool $withDetails = false): array
    {
        $base = [
            'id' => (int) $item->id,
            'uuid' => (string) $item->uuid,
            'filename' => (string) $item->filename,
            'original_name' => (string) ($item->original_name ?: $item->filename),
            'mime_type' => (string) $item->mime_type,
            'type' => (string) $item->type,
            'size' => (int) $item->size,
            'human_readable_size' => $item->human_readable_size,
            'disk' => (string) $item->disk,
            'path' => (string) $item->path,
            'width' => $item->width,
            'height' => $item->height,
            'folder_id' => $item->folder_id,
            'url' => (string) ($item->url ?: $item->full_url),
            'thumbnail_url' => $item->thumbnail_url,
            'medium_url' => $item->medium_url,
            'full_url' => $item->full_url,
            'download_url' => route('admin.media.download', ['media' => $item->id]),
            'created_at' => optional($item->created_at)?->toDateTimeString(),
            'deleted_at' => optional($item->deleted_at)?->toDateTimeString(),
        ];

        if (! $withDetails) {
            return $base;
        }

        $base['alt_text'] = $item->alt_text;
        $base['title'] = $item->title;
        $base['description'] = $item->description;
        $base['metadata'] = $item->metadata;
        $base['thumbnail_path'] = $item->thumbnail_path;
        $base['usage_count'] = $item->links()->count();

        return $base;
    }

    /**
     * @return array<string,string>|null
     */
    private function normalizeTranslatableInput(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $locale => $text) {
                $locale = trim((string) $locale);
                $text = trim((string) $text);
                if ($locale === '' || $text === '') {
                    continue;
                }

                $result[$locale] = $text;
            }

            return $result !== [] ? $result : null;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        return [app()->getLocale() => $text];
    }

    private function removeStoredFiles(Media $media): void
    {
        $disk = Storage::disk((string) $media->disk);
        $paths = [];

        if ($media->path !== null && $media->path !== '') {
            $paths[] = (string) $media->path;
        }

        if ($media->thumbnail_path !== null && $media->thumbnail_path !== '') {
            $paths[] = (string) $media->thumbnail_path;
        }

        $metadata = is_array($media->metadata) ? $media->metadata : [];
        $conversions = $metadata['conversions'] ?? [];
        if (is_array($conversions)) {
            foreach ($conversions as $conversionPath) {
                if (is_string($conversionPath) && $conversionPath !== '') {
                    $paths[] = $conversionPath;
                }
            }
        }

        $paths = array_values(array_unique($paths));
        foreach ($paths as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }

    private function resolveSourceFolderId(string $sourceContext): ?int
    {
        $rawSegments = collect(explode('/', str_replace('\\', '/', $sourceContext)))
            ->map(static fn ($segment): string => trim((string) $segment))
            ->filter(static fn (string $segment): bool => $segment !== '')
            ->values()
            ->all();

        if ($rawSegments === []) {
            return null;
        }

        $segments = ['Auto Uploads'];
        foreach ($rawSegments as $segment) {
            $normalized = $this->normalizeFolderSegment($segment);
            if ($normalized !== '') {
                $segments[] = $normalized;
            }
        }

        return $this->findOrCreateFolderPath($segments);
    }

    /**
     * @param  array<int, string>  $segments
     */
    private function findOrCreateFolderPath(array $segments): ?int
    {
        $parentId = null;

        foreach ($segments as $segment) {
            $slug = Str::slug($segment);
            if ($slug === '') {
                continue;
            }

            $existing = MediaFolder::query()
                ->where('parent_id', $parentId)
                ->where('slug', $slug)
                ->first();

            if ($existing instanceof MediaFolder) {
                $parentId = (int) $existing->id;

                continue;
            }

            $created = MediaFolder::query()->create([
                'parent_id' => $parentId,
                'name' => $segment,
                'slug' => $slug,
                'sort_order' => $this->nextFolderSortOrder($parentId),
            ]);

            $parentId = (int) $created->id;
        }

        return $parentId;
    }

    private function nextFolderSortOrder(?int $parentId): int
    {
        return (int) MediaFolder::query()
            ->where('parent_id', $parentId)
            ->max('sort_order') + 1;
    }

    private function normalizeFolderSegment(string $segment): string
    {
        $clean = trim(preg_replace('/\s+/', ' ', $segment) ?? '');
        $clean = str_replace(['..', '/', '\\'], ' ', $clean);

        return trim($clean);
    }
}