<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\KelompokRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KelompokRepository implements KelompokRepositoryInterface
{
    public function search(?string $term, ?string $unit, int $limit): Collection
    {
        return DB::table('kelompok')
            ->select('code_kel', 'nama_kel')
            ->when($unit, fn($q) => $q->where('code_unit', $unit))
            ->where(function ($q) use ($term) {
                $q->where('code_kel', 'like', '%' . $term . '%')
                    ->orWhere('nama_kel', 'like', '%' . $term . '%');
            })
            ->limit($limit)
            ->get();
    }
}
