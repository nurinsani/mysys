<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel yang mendapatkan SoftDeletes.
     *
     * TIDAK termasuk: tunggakan, pembiayaan_detail, tabel_rugi_laba, temp_akad_mus, pull_data
     * karena tabel-tabel tersebut sengaja di-delete secara hard dalam proses bisnis.
     */
    private array $tables = [
        'anggota',
        'pembiayaan',
        'simpanan',
        'simpanan_pokok',
        'simpanan_wajib',
        'kelompok',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                // softDeletes() menambahkan kolom deleted_at TIMESTAMP NULL DEFAULT NULL
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
