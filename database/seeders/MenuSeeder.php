<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Menu;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Menu::create([
            'name' => 'Dahsboard',
            'icon' => 'nav-icon fas fa-tachometer-alt',
            'parent_id' => null,
            'url' => '/admin', // Contoh URL
            'left' => 'null',
            'order' => 0,
            'role_id' => 1,
        ]);

        $masterData = Menu::create([
            'name' => 'Master Data Anggota',
            'icon' => 'nav-icon fas fa-users-cog', //
            'parent_id' => null,
            'url' => null,
            'left' => 'right fas fa-angle-left',
            'order' => 1,
            'role_id' => 1,

        ]);

        // Submenu
        Menu::create([
            'name' => 'Input Data Anggota',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $masterData->id,
            'url' => '/anggota', // Contoh URL
            'left' => 'null',
            'order' => 1,
            'role_id' => 1,

        ]);

        Menu::create([
            'name' => 'Input Data Pembiayaan',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $masterData->id,
            'url' => '/pembiayaan',
            'left' => 'null',
            'order' => 2,
            'role_id' => 1,

        ]);

        Menu::create([
            'name' => 'Input Data Kelompok',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $masterData->id,
            'url' => '/kelompok',
            'left' => 'null',
            'order' => 3,
            'role_id' => 1,

        ]);

        $CetakData = Menu::create([
            'name' => 'Cetak Dokumen',
            'icon' => 'nav-icon fas fa-print', //
            'parent_id' => null,
            'url' => null,
            'left' => 'right fas fa-angle-left',
            'order' => 4,
            'role_id' => 1,
        ]);

        // Submenu
        Menu::create([
            'name' => 'Cetak Approval',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $CetakData->id,
            'url' => '/cetak/approval',
            'left' => 'null',
            'order' => 4,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Cetak Wakalah',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $CetakData->id,
            'url' => '/anggota-baru', // Contoh URL
            'left' => 'null',
            'order' => 5,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Cetak Murabahah',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $CetakData->id,
            'url' => '/cetak/murabahah',
            'left' => 'null',
            'order' => 6,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Cetak Musyarokah',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $CetakData->id,
            'url' => '/cetak/musyarakah',
            'left' => 'null',
            'order' => 7,
            'role_id' => 1,
        ]);
        Menu::create([
            'name' => 'Cetak Cs',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $CetakData->id,
            'url' => '/cetak/cs',
            'left' => 'null',
            'order' => 8,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Cetak Cs WO',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $CetakData->id,
            'url' => '/cetak/cs_wo',
            'left' => 'null',
            'order' => 9,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Cetak Simapanan 5%',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $CetakData->id,
            'url' => '/cetak/simpanan-5-persen',
            'left' => 'null',
            'order' => 10,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Cetak Adendum',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $CetakData->id,
            'url' => '/cetak/adendum',
            'left' => 'null',
            'order' => 11,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Cetak Kartu Angsuran',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $CetakData->id,
            'url' => '/cetak/kartu-angsuran',
            'left' => 'null',
            'order' => 12,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Cetak La Risywah',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $CetakData->id,
            'url' => '/cetak/larisywah',
            'left' => 'null',
            'order' => 13,
            'role_id' => 1,
        ]);

        $Realisasi = Menu::create([
            'name' => 'Realisasi',
            'icon' => 'nav-icon fas fa-edit', //
            'parent_id' => null,
            'url' => null,
            'left' => 'right fas fa-angle-left',
            'order' => 14,
            'role_id' => 1,
        ]);

        // Submenu
        Menu::create([
            'name' => 'Realiasi Wakalah',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Realisasi->id,
            'url' => '/realisasi_wakalah',
            'left' => 'null',
            'order' => 14,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Realisasi Murabahah',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Realisasi->id,
            'url' => '/realisasi/murabahah',
            'left' => 'null',
            'order' => 15,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Pembatalan Wakalah',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Realisasi->id,
            'url' => '/realisasi/pembatalan-wakalah',
            'left' => 'null',
            'order' => 16,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Tagihan Kelompok',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Realisasi->id,
            'url' => '/realisasi/tagihan-kelompok',
            'left' => 'null',
            'order' => 17,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Hapus Buku',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Realisasi->id,
            'url' => '/realisasi/hapus-buku',
            'left' => 'null',
            'order' => 18,
            'role_id' => 1,
        ]);
        Menu::create([
            'name' => 'Realisasi Musyarokah',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Realisasi->id,
            'url' => '/realisasi-musyarakah',
            'left' => 'null',
            'order' => 18,
            'role_id' => 1,
        ]);

        $pemeliharaan_data = Menu::create([
            'name' => 'Pemeliharaan Data',
            'icon' => 'nav-icon fas fa-edit', //
            'parent_id' => null,
            'url' => null,
            'left' => 'right fas fa-angle-left',
            'order' => 19,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'View Data',
            'icon' => 'far fa-circle nav-ico',
            'parent_id' => $pemeliharaan_data->id,
            'url' => '/pemeliharaan/view-data',
            'left' => 'null',
            'order' => 19,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Pemeliharaan CIF',
            'icon' => 'far fa-circle nav-ico',
            'parent_id' => $pemeliharaan_data->id,
            'url' => '/pemeliharaan-cif',
            'left' => 'null',
            'order' => 20,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Kelompok',
            'icon' => 'far fa-circle nav-ico',
            'parent_id' => $pemeliharaan_data->id,
            'url' => '/pemeliharaan-kelompok',
            'left' => 'null',
            'order' => 21,
            'role_id' => 1,
        ]);

        $Transaksi = Menu::create([
            'name' => 'Transaksi',
            'icon' => 'nav-icon fas fa-money-bill', //
            'parent_id' => null,
            'url' => null,
            'left' => 'right fas fa-angle-left',
            'order' => 22,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Setoran',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Transaksi->id,
            'url' => '/transaksi/input-transaksi',
            'left' => 'null',
            'order' => 23,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Setoran 5%',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Transaksi->id,
            'url' => '/setoran-lima-persen',
            'left' => 'null',
            'order' => 24,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Setoran Perkelompok',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Transaksi->id,
            'url' => '/transaksi/setoran-perkelompok',
            'left' => 'null',
            'order' => 25,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'PB Perkelompok',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Transaksi->id,
            'url' => '/transaksi/pemindahbukuan-perkelompok',
            'left' => 'null',
            'order' => 26,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Setoran Beda Hari',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Transaksi->id,
            'url' => '/transaksi/setoran-beda-hari',
            'left' => 'null',
            'order' => 26,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Pelunasan Kelompok',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Transaksi->id,
            'url' => '/transaksi/pelunasan-kelompok',
            'left' => 'null',
            'order' => 27,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Jurnal Keluar',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Transaksi->id,
            'url' => '/transaksi/jurnal-keluar',
            'left' => 'null',
            'order' => 28,
            'role_id' => 1,
        ]);


        $Restrukturisasi = Menu::create([
            'name' => 'Restrukturisasi',
            'icon' => 'nav-icon fa-exchange-alt', //
            'parent_id' => null,
            'url' => null,
            'left' => 'right fas fa-angle-left',
            'order' => 29,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Kemampuan Bayar',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Restrukturisasi->id,
            'url' => '/rest-kemampuan-bayar',
            'left' => 'null',
            'order' => 30,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Jatuh Tempo',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Restrukturisasi->id,
            'url' => '/restrukturisasi/jatuh-tempo',
            'left' => 'null',
            'order' => 31,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'By Kelompok',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Restrukturisasi->id,
            'url' => '/restrukturisasi/by-kelompok',
            'left' => 'null',
            'order' => 32,
            'role_id' => 1,
        ]);

        $report = Menu::create([
            'name' => 'Report',
            'icon' => 'nav-icon fa-file-alt', //
            'parent_id' => null,
            'url' => null,
            'left' => 'right fas fa-angle-left',
            'order' => 33,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Report Tunggakan',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $report->id,
            'url' => '/report/tunggakan',
            'left' => 'null',
            'order' => 34,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Jurnal Masuk',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Transaksi->id,
            'url' => '/transaksi/jurnal-masuk',
            'left' => 'null',
            'order' => 37,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Mutasi Kas',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $report->id,
            'url' => '/report/mutasi-kas',
            'left' => 'null',
            'order' => 38,
            'role_id' => 1,
        ]);

        $new_report = Menu::create([
            'name' => 'New Report',
            'icon' => 'nav-icon fa-file-alt', //
            'parent_id' => null,
            'url' => null,
            'left' => 'right fas fa-angle-left',
            'order' => 39,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'List Jurnal',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $new_report->id,
            'url' => '/new-report/list-jurnal',
            'left' => 'null',
            'order' => 40,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Buku Besar',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $new_report->id,
            'url' => '/buku-besar',
            'left' => 'null',
            'order' => 40,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Report Nominative Pembiayaan',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $report->id,
            'url' => '/report/nominative-pembiayaan',
            'left' => 'null',
            'order' => 41,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Ekuitas',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $report->id,
            'url' => '/report/ekuitas',
            'left' => 'null',
            'order' => 42,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Report Nominative Simpanan',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $report->id,
            'url' => '/report/nominative-simpanan',
            'left' => 'null',
            'order' => 43,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Arus Kas',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $report->id,
            'url' => '/report/arus-kas',
            'left' => 'null',
            'order' => 44,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Neraca',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $report->id,
            'url' => '/report/neraca',
            'left' => 'null',
            'order' => 45,
            'role_id' => 1,
        ]);


        $mobcoll = Menu::create([
            'name' => 'Mobcoll',
            'icon' => 'nav-icon fa-file-alt', //
            'parent_id' => null,
            'url' => null,
            'left' => 'right fas fa-angle-left',
            'order' => 44,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Pull Data',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $mobcoll->id,
            'url' => '/pull-data',
            'left' => 'null',
            'order' => 45,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Transaksi CS Mobcol',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $mobcoll->id,
            'url' => '/cs_mobcol',
            'left' => 'null',
            'order' => 45,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Setorann Bank',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $mobcoll->id,
            'url' => '/setoran-bank',
            'left' => 'null',
            'order' => 45,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Report Mobcol',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $mobcoll->id,
            'url' => '/report-mobcol',
            'left' => 'null',
            'order' => 45,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Jurnal Umum',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Transaksi->id,
            'url' => '/transaksi/jurnal-umum',
            'left' => 'null',
            'order' => 46,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Posting Jurnal',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Transaksi->id,
            'url' => '/transaksi/posting-jurnal',
            'left' => 'null',
            'order' => 47,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'Hitung SHU',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $Transaksi->id,
            'url' => '/laporan/hitung-shu',
            'left' => 'null',
            'order' => 48,
            'role_id' => 1,
        ]);

        Menu::create([
            'name' => 'PPAP',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $report->id,
            'url' => '/report/ppap',
            'left' => 'null',
            'order' => 42,
            'role_id' => 1,
        ]);

     $report_kp = Menu::create([
           'name' => 'Report',
           'icon' => 'nav-icon fa-file-alt', //
           'parent_id' => null,
           'url' => null,
           'left' => 'right fas fa-angle-left',
           'order' => 42,
           'role_id' => 4,
       ]);

        Menu::create([
            'name' => 'Buku Besar',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $report_kp->id,
            'url' => '/buku-besar-kp',
            'left' => 'null',
            'order' => 43,
            'role_id' => 4,
        ]);

         Menu::create([
            'name' => 'List Jurnal',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $report_kp->id,
            'url' => '/list-jurnal-kp',
            'left' => 'null',
            'order' => 44,
            'role_id' => 4,
        ]);

         Menu::create([
            'name' => 'Nominative Pembiayaan',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $report_kp->id,
            'url' => '/nominative-pembiayaan-kp',
            'left' => 'null',
            'order' => 45,
            'role_id' => 4,
        ]);

        Menu::create([
            'name' => 'Neraca',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $report_kp->id,
            'url' => '/report/neraca-kp',
            'left' => 'null',
            'order' => 46,
            'role_id' => 4,
        ]);

        $transaksi_kp = Menu::create([
            'name' => 'Transaksi',
            'icon' => 'nav-icon fas fa-exchange-alt',
            'parent_id' => null,
            'url' => null,
            'left' => 'right fas fa-angle-left',
            'order' => 41,
            'role_id' => 4,
        ]);

        Menu::create([
            'name' => 'Posting Jurnal',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $transaksi_kp->id,
            'url' => '/transaksi/posting-jurnal-kp',
            'left' => 'null',
            'order' => 1,
            'role_id' => 4,
        ]);

        $approval_al = Menu::create([
           'name' => 'Approval',
           'icon' => 'nav-icon fa-file-alt', //
           'parent_id' => null,
           'url' => null,
           'left' => 'right fas fa-angle-left',
           'order' => 42,
           'role_id' => 2,
       ]);

        Menu::create([
            'name' => 'Pengajuan',
            'icon' => 'far fa-circle nav-icon',
            'parent_id' => $approval_al->id,
            'url' => '/al/approval-pengajuan',
            'left' => 'null',
            'order' => 45,
            'role_id' => 2,
        ]);

        Menu::create([
            'name' => 'Activity Log',
            'icon' => 'nav-icon fas fa-history',
            'parent_id' => null,
            'url' => '/admin/activity-log',
            'left' => 'null',
            'order' => 46,
            'role_id' => 1,
        ]);

    }
}
