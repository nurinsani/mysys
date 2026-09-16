<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\HasAuditTrail;

class simpanan_pokok extends Model
{
    use SoftDeletes, HasAuditTrail;

    protected $table = 'simpanan_pokok';
    protected $guarded = [];
}
