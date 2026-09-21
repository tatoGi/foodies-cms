<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Language;
use Illuminate\Pagination\LengthAwarePaginator;

interface LanguageRepositoryInterface
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
    public function getActiveLocales(): array;

    public function paginateForIndex(string $filter, string $search, int $perPage = 10): LengthAwarePaginator;

    /**
     * @return array<int, array{id:int,name:string,english_name:?string,code:string,country_code:?string,sort_order:int}>
     */
    public function getActiveLanguageSummaries(): array;

    public function create(array $data): Language;

    public function update(Language $language, array $data): Language;

    public function delete(Language $language): bool;

    public function setDefault(Language $language): void;

    public function bulkSetActive(array $ids, bool $active): void;

    public function updateSortOrder(array $orderedIds): void;

    public function getDefaultLanguageId(): ?int;

    /**
     * @return array<int, string>
     */
    public function localeUsageTables(string $locale): array;

    /**
     * Returns the default locale code (is_default=true), falling back to app.locale.
     */
    public function defaultLocale(): string;
}
