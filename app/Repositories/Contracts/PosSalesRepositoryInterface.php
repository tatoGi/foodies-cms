<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/** In-store sales sent by the POS as daily totals. Dates are bakery days; `$to` is exclusive. */
interface PosSalesRepositoryInterface
{
    /** @return array{bills: int, net: float, cash: float, card: float, other: float} */
    public function totals(?CarbonInterface $from = null, ?CarbonInterface $to = null): array;

    public function dishesSold(?CarbonInterface $from = null): int;

    /** @return Collection<int, array{product_name: string, total_qty: int, total_revenue: float}> */
    public function topProducts(?CarbonInterface $from = null, int $limit = 10): Collection;

    /** @return array<string, array{revenue: float, bills: int}> keyed by Y-m */
    public function monthly(int $months = 12): array;
}
