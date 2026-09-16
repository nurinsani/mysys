<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\HasAuditTrail;

class simpanan_wajib extends Model
{
    use SoftDeletes, HasAuditTrail;

    protected $table = 'simpanan_wajib';
    protected $guarded = [];
}
