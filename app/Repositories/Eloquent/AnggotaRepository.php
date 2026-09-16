<?php

namespace App\Repositories\Eloquent;

use App\Models\Anggota;
use App\Repositories\Contracts\AnggotaRepositoryInterface;

class AnggotaRepository implements AnggotaRepositoryInterface
{
    public function findByCif(string $cif): ?Anggota
    {
        return Anggota::where('cif', $cif)->first();
    }
}
