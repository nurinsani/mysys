<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Trait HasAuditTrail
 *
 * Otomatis mengisi kolom created_by, updated_by, dan ip_address
 * saat sebuah model Eloquent dibuat atau diupdate.
 *
 * Cara pakai: tambahkan `use HasAuditTrail;` di dalam class Model.
 * Pastikan tabel model tersebut sudah punya kolom:
 *   - created_by  VARCHAR(36) NULL
 *   - updated_by  VARCHAR(36) NULL
 *   - ip_address  VARCHAR(45) NULL
 */
trait HasAuditTrail
{
    protected static function bootHasAuditTrail(): void
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->ip_address = Request::ip();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
                $model->ip_address = Request::ip();
            }
        });
    }
}
