<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Language;
use App\Repositories\Contracts\LanguageRepositoryInterface;

class LanguageService
{
    public function __construct(
        private readonly LanguageRepositoryInterface $languageRepository,
    ) {}

    /**
     * @return array{languages:\Illuminate\Pagination\LengthAwarePaginator,activeLanguages:array<int, object{id:int,name:string,english_name:?string,code:string,country_code:?string,sort_order:int}>,filter:string,search:string}
     */
    public function buildIndexViewData(string $filter, string $search): array
    {
        $normalizedFilter = $filter === '' ? 'all' : $filter;

        return [
            'languages' => $this->languageRepository->paginateForIndex($normalizedFilter, $search, 10),
            'activeLanguages' => collect($this->languageRepository->getActiveLanguageSummaries())
                ->map(static fn (array $language): object => (object) $language)
                ->all(),
            'filter' => $normalizedFilter,
            'search' => $search,
        ];
    }

    /**
     * @param  array{name:string,english_name:string,code:string,country_code:string,direction:string,is_active?:bool}  $validated
     */
    public function create(array $validated): void
    {
        $this->languageRepository->create([
            'name' => trim($validated['name']),
            'english_name' => trim($validated['english_name']),
            'code' => trim(strtolower($validated['code'])),
            'country_code' => trim(strtoupper($validated['country_code'])),
            'direction' => $validated['direction'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'is_default' => false,
            'sort_order' => (((int) Language::query()->max('sort_order')) + 1),
        ]);
    }

    /**
     * @param  array{name:string,english_name:string,code:string,country_code:string,direction:string,is_active?:bool}  $validated
     * @return array{ok:bool,message:string}
     */
    public function update(Language $language, array $validated): array
    {
        $targetActiveState = (bool) ($validated['is_active'] ?? false);
        if ($language->is_default && $targetActiveState === false) {
            return [
                'ok' => false,
                'message' => __('Default language cannot be deactivated.'),
            ];
        }

        $this->languageRepository->update($language, [
            'name' => trim($validated['name']),
            'english_name' => trim($validated['english_name']),
            'code' => trim(strtolower($validated['code'])),
            'country_code' => trim(strtoupper($validated['country_code'])),
            'direction' => $validated['direction'],
            'is_active' => $targetActiveState,
        ]);

        return [
            'ok' => true,
            'message' => __('Language updated successfully.'),
        ];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public function destroy(Language $language): array
    {
        if ($language->is_default) {
            return [
                'ok' => false,
                'message' => __('Default language cannot be deleted.'),
            ];
        }

        $usageTables = $this->languageRepository->localeUsageTables((string) $language->code);
        if ($usageTables !== []) {
            return [
                'ok' => false,
                'message' => __('Cannot delete language because translations/content exist in related tables: :tables', [
                    'tables' => implode(', ', $usageTables),
                ]),
            ];
        }

        $this->languageRepository->delete($language);

        return [
            'ok' => true,
            'message' => __('Language deleted successfully.'),
        ];
    }

    /**
     * @return array{ok:bool,message:string}
     */
    public function toggleActive(Language $language, ?bool $active): array
    {
        $targetState = $active ?? ! $language->is_active;

        if ($language->is_default && $targetState === false) {
            return [
                'ok' => false,
                'message' => __('Default language cannot be deactivated.'),
            ];
        }

        $this->languageRepository->update($language, [
            'is_active' => $targetState,
        ]);

        return [
            'ok' => true,
            'message' => __('Language status updated.'),
        ];
    }

    public function setDefault(Language $language): void
    {
        $this->languageRepository->setDefault($language);
    }

    /**
     * @param  array<int,int>  $selectedIds
     * @return array{ok:bool,message:string}
     */
    public function bulkAction(string $action, array $selectedIds): array
    {
        $ids = collect($selectedIds)
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($action === 'activate') {
            $this->languageRepository->bulkSetActive($ids->all(), true);

            return [
                'ok' => true,
                'message' => __('Selected languages activated.'),
            ];
        }

        $defaultId = $this->languageRepository->getDefaultLanguageId();

        if ($defaultId !== null && $ids->contains((int) $defaultId)) {
            return [
                'ok' => false,
                'message' => __('Default language cannot be deactivated.'),
            ];
        }

        $this->languageRepository->bulkSetActive($ids->all(), false);

        return [
            'ok' => true,
            'message' => __('Selected languages deactivated.'),
        ];
    }

    /**
     * @param  array<int,int>  $orderedIds
     */
    public function updateSortOrder(array $orderedIds): void
    {
        $ids = collect($orderedIds)
            ->map(static fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->languageRepository->updateSortOrder($ids);
    }
}
