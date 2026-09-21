<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Language;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LanguageRepository implements LanguageRepositoryInterface
{
    /**
     * @return array<int, array{
     *     code:string,
     *     name:string,
     *     english_name:string,
     *     flag:string,
     *     country_code:string,
     *     direction:string,
     *     is_default:bool,
     *     is_active:bool
     * }>
     */
    public function getActiveLocales(): array
    {
        $languages = Language::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('english_name')
            ->get(['code', 'name', 'english_name', 'country_code', 'direction', 'is_default', 'is_active']);

        if ($languages->isEmpty()) {
            $fallbackCode = app()->getLocale();

            return [[
                'code' => $fallbackCode,
                'name' => strtoupper($fallbackCode),
                'english_name' => strtoupper($fallbackCode),
                'flag' => '',
                'country_code' => '',
                'direction' => 'ltr',
                'is_default' => true,
                'is_active' => true,
            ]];
        }

        return $languages
            ->map(static function (Language $language): array {
                $code = (string) $language->code;

                return [
                    'code' => $code,
                    'name' => (string) ($language->name ?: strtoupper($code)),
                    'english_name' => (string) ($language->english_name ?: strtoupper($code)),
                    'flag' => (string) $language->country_code,
                    'country_code' => (string) $language->country_code,
                    'direction' => (string) ($language->direction ?: 'ltr'),
                    'is_default' => (bool) $language->is_default,
                    'is_active' => (bool) $language->is_active,
                ];
            })
            ->values()
            ->all();
    }

    public function paginateForIndex(string $filter, string $search, int $perPage = 10): LengthAwarePaginator
    {
        $query = Language::query();

        if ($filter === 'active') {
            $query->where('is_active', true);
        } elseif ($filter === 'inactive') {
            $query->where('is_active', false);
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('english_name', 'like', '%'.$search.'%')
                    ->orWhere('code', 'like', '%'.$search.'%');
            });
        }

        return $query
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('english_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<int, array{id:int,name:string,english_name:?string,code:string,country_code:?string,sort_order:int}>
     */
    public function getActiveLanguageSummaries(): array
    {
        return Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('english_name')
            ->get(['id', 'name', 'english_name', 'code', 'country_code', 'sort_order'])
            ->map(static fn (Language $language): array => [
                'id' => (int) $language->id,
                'name' => (string) $language->name,
                'english_name' => $language->english_name !== null ? (string) $language->english_name : null,
                'code' => (string) $language->code,
                'country_code' => $language->country_code !== null ? (string) $language->country_code : null,
                'sort_order' => (int) $language->sort_order,
            ])
            ->values()
            ->all();
    }

    public function create(array $data): Language
    {
        return Language::query()->create($data);
    }

    public function update(Language $language, array $data): Language
    {
        $language->update($data);

        return $language;
    }

    public function delete(Language $language): bool
    {
        return (bool) $language->delete();
    }

    public function setDefault(Language $language): void
    {
        DB::transaction(function () use ($language): void {
            Language::query()->update(['is_default' => false]);

            $language->update([
                'is_default' => true,
                'is_active' => true,
            ]);
        });
    }

    public function bulkSetActive(array $ids, bool $active): void
    {
        Language::query()
            ->whereIn('id', $ids)
            ->update(['is_active' => $active]);
    }

    public function updateSortOrder(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds): void {
            foreach ($orderedIds as $index => $languageId) {
                Language::query()
                    ->where('id', (int) $languageId)
                    ->update(['sort_order' => $index + 1]);
            }
        });
    }

    public function getDefaultLanguageId(): ?int
    {
        $defaultId = Language::query()
            ->where('is_default', true)
            ->value('id');

        return $defaultId !== null ? (int) $defaultId : null;
    }

    public function defaultLocale(): string
    {
        if (! Schema::hasTable('languages')) {
            return config('app.locale', 'en');
        }

        $code = Language::query()->where('is_default', true)->value('code');

        return is_string($code) && $code !== '' ? $code : config('app.locale', 'en');
    }

    /**
     * @return array<int, string>
     */
    public function localeUsageTables(string $locale): array
    {
        $tables = DB::table('information_schema.COLUMNS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('COLUMN_NAME', 'locale')
            ->pluck('TABLE_NAME')
            ->map(static fn ($table): string => (string) $table)
            ->filter(static function (string $table): bool {
                return preg_match('/^[A-Za-z0-9_]+$/', $table) === 1;
            })
            ->values()
            ->all();

        $usedTables = [];
        foreach ($tables as $table) {
            if (DB::table($table)->where('locale', $locale)->exists()) {
                $usedTables[] = $table;
            }
        }

        return $usedTables;
    }
}
