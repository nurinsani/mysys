<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            if (!Schema::hasColumn('anggota', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('anggota', function (Blueprint $table) {
            if (!Schema::hasColumn('anggota', 'created_by')) {
                $table->string('created_by', 36)->nullable()->after('deleted_at');
            }
            if (!Schema::hasColumn('anggota', 'updated_by')) {
                $table->string('updated_by', 36)->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('anggota', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('updated_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anggota', function (Blueprint $table) {
            if (Schema::hasColumn('anggota', 'ip_address')) {
                $table->dropColumn('ip_address');
            }
            if (Schema::hasColumn('anggota', 'updated_by')) {
                $table->dropColumn('updated_by');
            }
            if (Schema::hasColumn('anggota', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('anggota', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
