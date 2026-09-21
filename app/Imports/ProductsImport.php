<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
{
    private int $imported = 0;

    private int $updated = 0;

    private array $errors = [];

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            try {
                $sku = trim((string) ($row['sku'] ?? ''));

                if ($sku === '') {
                    $this->errors[] = "Row {$rowNum}: SKU is empty — skipped.";

                    continue;
                }

                $price = (float) ($row['price'] ?? 0);

                if ($price < 0 || $price > 999999) {
                    $this->errors[] = "Row {$rowNum}: invalid price '{$price}' — skipped (check for merged cells or concatenated values).";

                    continue;
                }

                $colors = $this->parseColors((string) ($row['colors'] ?? ''));

                // Support both 'is_active'/'active' and 'is_featured'/'featured' header variants
                $isActive = $this->resolveBool($row, ['is_active', 'active'], true);
                $isFeatured = $this->resolveBool($row, ['is_featured', 'featured'], false);

                $attributes = [
                    'brand' => $this->str($row['brand'] ?? null),
                    'price' => $price,
                    'sale_price' => $this->nullableFloat($row['sale_price'] ?? null),
                    'on_sale' => $this->resolveBool($row, ['on_sale'], false),
                    'category' => $this->str($row['category'] ?? null),
                    'stock' => (int) ($row['stock'] ?? 0),
                    'colors' => $colors ?: null,
                    'is_active' => $isActive,
                    'is_featured' => $isFeatured,
                    'published' => true,
                    'deleted_at' => null,
                ];

                $existing = Product::withTrashed()->where('sku', $sku)->first();

                if ($existing !== null) {
                    $existing->fill($attributes)->save();
                    $product = $existing;
                    $wasNew = false;
                } else {
                    $product = Product::create(array_merge(['sku' => $sku], $attributes));
                    $wasNew = true;
                }

                $this->upsertTranslation($product, 'ka', $row, 'ka');
                $this->upsertTranslation($product, 'en', $row, 'en');

                $wasNew ? $this->imported++ : $this->updated++;
            } catch (\Throwable $e) {
                $this->errors[] = "Row {$rowNum}: ".$e->getMessage();
            }
        }
    }

    public function getImported(): int
    {
        return $this->imported;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function upsertTranslation(Product $product, string $locale, Collection $row, string $suffix): void
    {
        $title = trim((string) ($row["title_{$suffix}"] ?? ''));

        if ($title === '') {
            return;
        }

        // Str::slug returns empty for Georgian — fall back to product-{id}-{locale}
        $slug = Str::slug($title);
        if ($slug === '') {
            $slug = 'product-'.$product->id.'-'.$locale;
        }

        // Ensure uniqueness: if another product already has this slug, append id
        $conflict = ProductTranslation::where('locale', $locale)
            ->where('slug', $slug)
            ->where('product_id', '!=', $product->id)
            ->exists();

        if ($conflict) {
            $slug .= '-'.$product->id;
        }

        ProductTranslation::updateOrCreate(
            ['product_id' => $product->id, 'locale' => $locale],
            [
                'title' => $title,
                'slug' => $slug,
                'excerpt' => $this->str($row["excerpt_{$suffix}"] ?? null),
                'content' => $this->str($row["content_{$suffix}"] ?? null),
                'meta_title' => $title,
            ]
        );
    }

    /**
     * Try multiple key names and return the first non-empty value as bool.
     *
     * @param  array<string>  $keys
     */
    private function resolveBool(Collection $row, array $keys, bool $default): bool
    {
        foreach ($keys as $key) {
            $val = $row[$key] ?? null;
            if ($val !== null && (string) $val !== '') {
                return (bool) $val;
            }
        }

        return $default;
    }

    private function nullableFloat(mixed $val): ?float
    {
        $v = trim((string) ($val ?? ''));

        return ($v !== '' && is_numeric($v)) ? (float) $v : null;
    }

    private function str(mixed $val): ?string
    {
        $v = trim((string) ($val ?? ''));

        return $v !== '' ? $v : null;
    }

    private function parseColors(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
