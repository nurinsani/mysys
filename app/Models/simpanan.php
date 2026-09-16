<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class simpanan extends Model
{
    /**
     * Tabel `simpanan` (beda dari simpanan_pokok/simpanan_wajib) tidak lagi
     * punya kolom deleted_at/created_by/updated_by/ip_address di skema live
     * — jadi SoftDeletes & HasAuditTrail (dari Sesi 1.2/1.3) dilepas di sini
     * saja. simpanan_pokok/simpanan_wajib tetap pakai keduanya, kolomnya
     * masih ada di tabel masing-masing.
     */
    use LogsActivity;

    protected $table = 'simpanan';
    protected $guarded = [];
    public $timestamps = false;
    protected $primaryKey = 'reff';

    /**
     * Sesi 3.1 — lihat catatan di app/Models/Anggota.php soal cakupan
     * LogsActivity (cuma jalur Eloquent, bukan DB::table() mentah — dan
     * mayoritas insert ke tabel simpanan di aplikasi ini lewat DB::table()).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['debet', 'kredit', 'type'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
