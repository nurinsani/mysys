<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface KelompokRepositoryInterface
{
    /**
     * Cari kelompok berdasarkan code_kel/nama_kel (LIKE, kedua kolom),
     * opsional dibatasi ke satu unit.
     */
    public function search(?string $term, ?string $unit, int $limit): Collection;
}
