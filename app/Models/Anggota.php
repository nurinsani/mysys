<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Http\Traits\HasAuditTrail;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Models\Activity;


class Anggota extends Model
{
    use HasFactory, SoftDeletes, HasAuditTrail, LogsActivity;

    /**
     * Sesi 3.1 — LogsActivity cuma menangkap perubahan lewat Eloquent
     * (create/update/delete lewat model ini). Sebagian besar tulis-data di
     * aplikasi ini masih lewat DB::table() mentah (lihat catatan di
     * rencana_pengerjaan.md) — itu TIDAK tercatat di sini.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'unit', 'kode_kel', 'cao'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * subject_id di tabel activity_log BIGINT, sedangkan primary key `no`
     * varchar (leading zero-nya hilang kalau disimpan sebagai subject_id) —
     * jadi CIF disisipkan langsung ke `properties` supaya tetap bisa
     * ditelusuri walau subject_id-nya tidak match.
     */
    public function tapActivity(Activity $activity, string $eventName): void
    {
        $activity->properties = $activity->properties->merge([
            'cif' => $this->cif,
            'no_anggota' => $this->no,
        ]);
    }

    protected $table = 'anggota';
    protected $guarded = [];
    protected $primaryKey = 'no';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'no' => 'string',
    ];

    public function temp_mus_akad()
    {
        return $this->hasMany(temp_akad_mus::class, 'no_anggota', 'no');
    }

    public function pembiayaan()
    {
        return $this->hasMany(pembiayaan::class, 'no_anggota', 'no');
    }
}
