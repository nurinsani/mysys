<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\HasAuditTrail;


class Kelompok extends Model
{
    protected $table = 'kelompok';
    use HasFactory, SoftDeletes, HasAuditTrail;
    protected $guarded = [];
    protected $primaryKey = 'code_kel';

    protected $casts = [
        'code_kel' => 'string',
    ];
}
