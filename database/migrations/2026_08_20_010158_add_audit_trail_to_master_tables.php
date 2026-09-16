<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel master yang mendapatkan kolom audit trail lengkap.
     * Semua kolom NULLABLE karena tabel sudah berisi data.
     */
    private array $masterTables = [
        'anggota',
        'pembiayaan',
        'simpanan',
        'simpanan_pokok',
        'simpanan_wajib',
        'kelompok',
    ];

    public function up(): void
    {
        // Tambahkan audit trail ke tabel-tabel master
        foreach ($this->masterTables as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                // Semua kolom nullable karena data lama belum ada audit trail
                // Posisi ->after('deleted_at') karena semua tabel sudah punya deleted_at (Sesi 1.2)
                $table->string('created_by', 36)->nullable()->after('deleted_at');
                $table->string('updated_by', 36)->nullable()->after('created_by');
                $table->string('ip_address', 45)->nullable()->after('updated_by');
            });
        }

        // tabel_transaksi sudah punya id_admin, cukup tambahkan ip_address
        Schema::table('tabel_transaksi', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('id_admin');
        });
    }

    public function down(): void
    {
        foreach ($this->masterTables as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropColumn(['created_by', 'updated_by', 'ip_address']);
            });
        }

        Schema::table('tabel_transaksi', function (Blueprint $table) {
            $table->dropColumn('ip_address');
        });
    }
};
