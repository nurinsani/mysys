<?php

namespace App\Repositories\Contracts;

use App\Models\Anggota;

interface AnggotaRepositoryInterface
{
    public function findByCif(string $cif): ?Anggota;
}
