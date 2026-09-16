<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Daftar permission granular per modul.
     * Belum dipasang ke middleware/controller manapun — sengaja hanya
     * menyediakan datanya dulu (paralel dengan role_id lama), supaya
     * role_id + RoleMiddleware yang sedang dipakai produksi tidak terganggu.
     */
    public function run(): void
    {
        $permissions = [
            // Master Data Anggota
            'anggota.view',
            'anggota.create',
            'anggota.edit',
            'anggota.delete',
            'anggota.export',

            // Pembiayaan
            'pembiayaan.view',
            'pembiayaan.create',
            'pembiayaan.edit',
            'pembiayaan.delete',

            // Kelompok
            'kelompok.view',
            'kelompok.create',
            'kelompok.edit',
            'kelompok.delete',

            // Cetak Dokumen
            'cetak.approval',
            'cetak.wakalah',
            'cetak.murabahah',
            'cetak.musyarakah',
            'cetak.cs',
            'cetak.cs_wo',
            'cetak.simpanan_lima_persen',
            'cetak.adendum',
            'cetak.kartu_angsuran',
            'cetak.la_risywah',

            // Realisasi
            'realisasi.wakalah',
            'realisasi.murabahah',
            'realisasi.musyarokah',
            'realisasi.pembatalan_wakalah',
            'realisasi.tagihan_kelompok',
            'realisasi.hapus_buku',

            // Pemeliharaan Data
            'pemeliharaan.view_data',
            'pemeliharaan.cif',
            'pemeliharaan.kelompok',

            // Transaksi
            'transaksi.setoran.proses',
            'transaksi.setoran_lima_persen.proses',
            'transaksi.setoran_perkelompok.proses',
            'transaksi.setoran_beda_hari.proses',
            'transaksi.pemindahbukuan_perkelompok.proses',
            'transaksi.pelunasan_kelompok.proses',
            'transaksi.jurnal_keluar.proses',
            'transaksi.jurnal_masuk.proses',
            'transaksi.jurnal_umum.proses',
            'transaksi.posting_jurnal.proses',
            'transaksi.hitung_shu.proses',

            // Restrukturisasi
            'restrukturisasi.kemampuan_bayar',
            'restrukturisasi.jatuh_tempo',
            'restrukturisasi.by_kelompok',

            // Report
            'report.tunggakan.view',
            'report.mutasi_kas.view',
            'report.list_jurnal.view',
            'report.buku_besar.view',
            'report.nominatif_pembiayaan.view',
            'report.ekuitas.view',
            'report.nominatif_simpanan.view',
            'report.arus_kas.view',
            'report.neraca.view',
            'report.ppap.view',

            // Mobcoll
            'mobcoll.pull_data',
            'mobcoll.transaksi_cs',
            'mobcoll.setoran_bank',
            'mobcoll.report',

            // Approval (AL)
            'approval.pengajuan.view',
            'approval.pengajuan.proses',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
