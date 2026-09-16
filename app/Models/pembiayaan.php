<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\HasAuditTrail;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class pembiayaan extends Model
{

    protected $table = 'pembiayaan';
    use HasFactory, SoftDeletes, HasAuditTrail, LogsActivity;

    protected $guarded = [];
    protected $primaryKey = 'cif';
    protected $casts = [
        'cif' => 'string',
    ];

    /**
     * Sesi 3.1 — lihat catatan di app/Models/Anggota.php soal cakupan
     * LogsActivity (cuma jalur Eloquent, bukan DB::table() mentah).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['os', 'status', 'run_tenor', 'ke'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
