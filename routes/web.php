<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AhController;
use App\Http\Controllers\AlController;
use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\ApprovalPengajuanController;
use App\Http\Controllers\CetakAdendumController;
use App\Http\Controllers\CetakKartuAngsuranController;
use App\Http\Controllers\CetakMurabahahController;
use App\Http\Controllers\RealisasiWakalahController;
use App\Http\Controllers\RealisasiMurabahahController;
use App\Http\Controllers\CetakMusyarakahController;
use App\Http\Controllers\CetakSimpananLimaPersenController;
use App\Http\Controllers\CetakCsController;
use App\Http\Controllers\CetakLaRisywahController;
use App\Http\Controllers\KelompokController;
use App\Http\Controllers\PDFController;
use App\Http\Controllers\CetakCsWoController;
use App\Http\Controllers\CetakApprovalController;
use App\Http\Controllers\InputTransaksiController;
use App\Http\Controllers\PembiayaanController;
use App\Http\Controllers\PemeliharaanKelompok;
use App\Http\Controllers\ViewDataController;
use Illuminate\Contracts\View\View;
use App\Http\Controllers\PembatalanWakalahController;
use App\Http\Controllers\RealisasiTagihanKelompokController;
use App\Http\Controllers\SetoranLimaPersenController;
use App\Http\Controllers\RestKemampuanBayarController;
use App\Http\Controllers\RealisasiMusyarokahController;
use App\Http\Controllers\HapusBukuController;
use App\Http\Controllers\JurnalMasukController;
use App\Http\Controllers\JurnalKeluarController;
use App\Http\Controllers\JurnalUmumController;
use App\Http\Controllers\PostingJurnalController;
use App\Http\Controllers\HitungShuController;
use App\Http\Controllers\MutasiKasController;
use App\Http\Controllers\ListJurnalController;
use App\Http\Controllers\ReportMutasiController;
use App\Http\Controllers\ReportTunggakanController;
use App\Http\Controllers\PelunasanKelompokController;
use App\Http\Controllers\PelunasanController;
use App\Http\Controllers\PemindahbukuanPerkelompokController;
use App\Http\Controllers\ReportEkuitasController;
use App\Http\Controllers\ReportArusKasController;
use App\Http\Controllers\ReportNeracaController;
use App\Http\Controllers\ReportNeracaKpController;
use App\Http\Controllers\PostingJurnalKpController;
use App\Http\Controllers\ReportNominativeSimpananController;
use App\Http\Controllers\ReportNominativePembiayaanController;
use App\Http\Controllers\RestrukturisasiJatuhTempoController;
use App\Http\Controllers\RestrukturisasiByKelompokController;
use App\Http\Controllers\SetoranPerkelompokController;
use App\Http\Controllers\SetoranBedaHariController;
use App\Http\Controllers\PullDataController;
use App\Http\Controllers\ExportMobcolController;
use App\Http\Controllers\SetoranBankController;
use App\Http\Controllers\ReportMobcolController;
use App\Http\Controllers\BukuBesarController;
use App\Http\Controllers\KpController;
use App\Http\Controllers\ReportPpapController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BukuBesarKpController;
use App\Http\Controllers\ListJurnalKpController;
use App\Http\Controllers\ReportNominatifPembiayaanKpController;
use Illuminate\Support\Facades\App;

//  jika user belum login
Route::group(['middleware' => 'guest'], function () {
    Route::get('/', [AuthController::class, 'login'])->name('login');
    Route::post('/', [AuthController::class, 'dologin']);

});

// untuk Admin AL login
Route::group(['middleware' => ['auth', 'role:1,2,3,4']], function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/redirect', [RedirectController::class, 'check']);
    Route::get('/redirect', [RedirectController::class, 'check']);
});


// untuk Admin
Route::group(['middleware' => ['auth', 'role:1']], function () {
    Route::get('/admin', [AdminController::class, 'index']);
    Route::get('/admin/activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
    Route::get('/realisasi_wakalah', [RealisasiWakalahController::class, 'index']);
    Route::POST('/proses_realisasi_wakalah', [RealisasiWakalahController::class, 'realisasiWakalah']);
    Route::get('realisasi_wakalah/getData', [RealisasiWakalahController::class, 'getData']);
    Route::get('realisasi_wakalah/cari-kelompok', [RealisasiWakalahController::class, 'cariKelompok']);
    Route::get('/cetak/cs', [CetakCsController::class, 'index'])->name('cetak.cs.index');
    Route::get('/cetak/kode_ao', [CetakCsController::class, 'cariAo'])->name('cetak.kode.ao');
    Route::get('/cetak/pdf_cs', [CetakCsController::class, 'pdfCs'])->name('pdfCs');
    Route::get('/cetak/cs_wo', [CetakCsWoController::class, 'index'])->name('cetak.cs.wo.index');
    Route::get('/cetak/pdf_cs_wo', [CetakCsWoController::class, 'pdfCsWo'])->name('pdfCsWo');

    Route::get('/cetak/musyarakah', [CetakMusyarakahController::class, 'index'])->name('cetak_musyarakah');
    Route::post('/cetak/musyarakah/result', [CetakMusyarakahController::class, 'hasil'])->name('form_musyarakah');
    Route::get('/cetak/larisywah', [CetakLaRisywahController::class, 'index'])->name('cetak_larisywah');
    Route::post('/cetak/larisywah/result', [CetakLaRisywahController::class, 'hasil'])->name('form_larisywah');

    Route::get('/cetak/approval', [CetakApprovalController::class, 'index'])->name('cetak_approval');
    Route::post('/cetak/approval/result', [CetakApprovalController::class, 'hasil'])->name('form_approval');
    Route::get('/realisasi/murabahah', [RealisasiMurabahahController::class, 'index'])->name('realisasi_murabahah');
    Route::get('/realisasi/murabahah/search', [RealisasiMurabahahController::class, 'search'])->name('realisasi.search');
    Route::post('/realisasi/murabahah/update', [RealisasiMurabahahController::class, 'updateStatus'])->name('realisasi.update');
    Route::get('/realisasi/murabahah/cari-kelompok', [RealisasiMurabahahController::class, 'cariKelompok'])->name('realisasiMurabahah.cariKelompok');

    Route::get('/kelompok/next-code', [KelompokController::class, 'getNextCode'])->name('kelompok.nextCode');
    Route::get('/kelompok/data', [KelompokController::class, 'data'])->name('kelompok.data');
    Route::resource('kelompok', KelompokController::class);

    Route::get('/cetak/murabahah', [CetakMurabahahController::class, 'index'])->name('cetakMurabahah');
    Route::post('/cetak/murabahah/filter', [CetakMurabahahController::class, 'filter'])->name('cetakMurabahah.filter');
    Route::post('/cetak/murabahah/pdf', [CetakMurabahahController::class, 'cetakPDF'])->name('cetakMurabahah.pdf');

    Route::get('/cetak/simpanan-5-persen', [CetakSimpananLimaPersenController::class, 'index'])->name('cetak-simpanan-5-persen');
    Route::post('/cetak/simpanan-5-persen/filter', [CetakSimpananLimaPersenController::class, 'filter'])->name('cetakSimpanan5Persen.filter');
    Route::post('/cetak/simpanan-5-persen/pdf', [CetakSimpananLimaPersenController::class, 'cetakPDF'])->name('cetakSimpanan5Persen.pdf');

    Route::get('/cetak/adendum', [CetakAdendumController::class, 'index'])->name('cetakAdendum');
    Route::post('/cetak/adendum/filter', [CetakAdendumController::class, 'filter'])->name('cetakAdendum.filter');
    Route::post('/cetak/adendum/pdf', [CetakAdendumController::class, 'cetakPDF'])->name('cetakAdendum.pdf');

    Route::get('/cetak/kartu-angsuran', [CetakKartuAngsuranController::class, 'index'])->name('cetakkartuAngsuran');
    Route::post('/cetak/kartu-angsuran/filter', [CetakKartuAngsuranController::class, 'filter'])->name('cetakkartuAngsuran.filter');
    Route::post('/cetak/kartu-angsuran/pdf', [CetakKartuAngsuranController::class, 'cetakPDF'])->name('cetakkartuAngsuran.pdf');

    Route::get('/anggota/data', [AnggotaController::class, 'data'])->name('anggota.data');
    Route::get('anggota/cari', [AnggotaController::class, 'cari'])->name('anggota.cari');
    Route::get('anggota/get-kelompok-data', [AnggotaController::class, 'getKelompokData']);
    Route::post('anggota/cari-ktp', [AnggotaController::class, 'cariKtp']);
    Route::get('/anggota/export', [AnggotaController::class, 'export'])->name('anggota.export');
    Route::resource('anggota', AnggotaController::class)->except(['show', 'destroy']);
    Route::get('anggota/get-kelompok/{cao}', [AnggotaController::class, 'getKelompokByCao']);

    Route::get('/get-anggota/{cif}', [KelompokController::class, 'getAnggotaByCif']);

    Route::get('/pembiayaan', [PembiayaanController::class, 'index'])->name('pembiayaan.index');
    Route::get('/pembiayaan/cari-kelompok', [PembiayaanController::class, 'cariKelompok'])->name('pembiayaan.cariKelompok');
    Route::get('/pembiayaan/data', [PembiayaanController::class, 'data'])->name('pembiayaan.data');
    Route::post('/pembiayaan/add/{cif}', [PembiayaanController::class, 'addPembiayaan'])->name('pembiayaan.add');
    Route::get('/pembiayaan/edit/{cif}', [PembiayaanController::class, 'edit'])->name('pembiayaan.edit');

    Route::get('pemeliharaan/view-data/data', [ViewDataController::class, 'data'])->name('viewData.data');
    Route::resource('pemeliharaan/view-data', ViewDataController::class);

    Route::get('/pemeliharaan-kelompok/data', [PemeliharaanKelompok::class, 'data'])->name('pemeliharaan-kelompok.data');
    Route::get('/pemeliharaan-kelompok/get-anggota', [PemeliharaanKelompok::class, 'getAnggota'])->name('pemeliharaan-kelompok.getAnggota');
    Route::resource('pemeliharaan-kelompok', PemeliharaanKelompok::class);

    Route::get('/realisasi/hapus-buku', [HapusBukuController::class, 'index'])->name('hapus_buku');
    Route::get('/realisasi/hapus-buku/search-cif', [HapusBukuController::class, 'searchCif'])->name('hapus_buku.search_cif');
    Route::post('/realisasi/hapus-buku/add-transaction', [HapusBukuController::class, 'addTransaction'])->name('hapus_buku.add_transaction');
    Route::post('/realisasi/hapus-buku/delete-transaction', [HapusBukuController::class, 'deleteTransaction'])->name('hapus_buku.delete_transaction');
    Route::post('/realisasi/hapus-buku/process-all', [HapusBukuController::class, 'processAll'])->name('hapus_buku.process_all');

    Route::get('/restrukturisasi/jatuh-tempo', [RestrukturisasiJatuhTempoController::class, 'index'])->name('jatuh_tempo');
    Route::post('/restrukturisasi/jatuh-tempo/search', [RestrukturisasiJatuhTempoController::class, 'searchKelompok'])->name('jatuh_tempo.searchKelompok');
    Route::post('/restrukturisasi/jatuh-tempo/restrukturisasi', [RestrukturisasiJatuhTempoController::class, 'restrukturisasi'])->name('jatuh_tempo.restrukturisasi');

    Route::get('/restrukturisasi/by-kelompok', [RestrukturisasiByKelompokController::class, 'index'])->name('rest_kelompok');
    Route::get('/restrukturisasi/by-kelompok/suggest-kelompok', [RestrukturisasiByKelompokController::class, 'suggestKelompok'])->name('rest_kelompok.suggest_kelompok');
    Route::post('/restrukturisasi/by-kelompok/search', [RestrukturisasiByKelompokController::class, 'searchKelompok'])->name('rest_kelompok.searchKelompok');
    Route::post('/restrukturisasi/by-kelompok/restrukturisasi', [RestrukturisasiByKelompokController::class, 'restrukturisasi'])->name('rest_kelompok.restrukturisasi');

    // DOMpdf
    Route::get('/pdf/generate/{feature}/{date}', [PDFController::class, 'generateMusyarakahPdf'])->name('pdf.generateMusyarakah');
    Route::get('/pdf/generate/{feature}/{kelompok}/{date}', [PDFController::class, 'generateLaRisywahPdf'])->name('pdf.generateLaRisywah');
    Route::get('/pdf/generate/{feature}/{date}', [PDFController::class, 'generateApprovalPdf'])->name('pdf.generateApproval');

    Route::get('/realisasi/pembatalan-wakalah', [PembatalanWakalahController::class, 'index'])->name('pembatalan_wakalah');
    Route::get('/realisasi/pembatalan-wakalah/data', [PembatalanWakalahController::class, 'data'])->name('pembatalan_wakalah.data');
    Route::post('/realisasi/pembatalan-wakalah/realisasi', [PembatalanWakalahController::class, 'realisasi'])->name('pembatalan_wakalah.realisasi');

    Route::get('/setoran-lima-persen', [SetoranLimaPersenController::class, 'index']);
    Route::get('/setoran-lima-persen-get-kelompok', [SetoranLimaPersenController::class, 'getSetKelompok']);
    Route::get('/setoran-lima-persen/getData', [SetoranLimaPersenController::class, 'getData']);
    Route::post('/proses-realisasi-lima-persen', [SetoranLimaPersenController::class, 'realisasiLimaPersen']);

    //Rest Kemampuan Bayar
    Route::get('/rest-kemampuan-bayar', [RestKemampuanBayarController::class, 'index']);
    Route::get('/rest-kemampuan-bayar-get-kelompok', [RestKemampuanBayarController::class, 'getSetKelompok']);
    Route::get('/rest-kemampuan-bayar/getData', [RestKemampuanBayarController::class, 'getData']);
    Route::post('/proses-rest-kemampuan-bayar', [RestKemampuanBayarController::class, 'realisasiRestKemampuanBayar']);


    // input transaksi
    Route::get('/transaksi/input-transaksi', [InputTransaksiController::class, 'index']);
    Route::get('/transaksi/input-transaksi/get-cif/{cif}', [InputTransaksiController::class, 'getByCif']);
    Route::post('/transaksi/input-transaksi', [InputTransaksiController::class, 'store'])->name('transaksi.store');
    Route::get('/transaksi/input-transaksi/history/{cif}', [InputTransaksiController::class, 'getHistory']);

    // realisasi tagihan kelompok
    Route::get('/realisasi/tagihan-kelompok', [RealisasiTagihanKelompokController::class, 'index']);
    Route::post('/realisasi/tagihan-kelompok/get-kelompok', [RealisasiTagihanKelompokController::class, 'getKelompok'])->name('realisasi.tagihanKelompok.getKelompok');
    Route::post('/realisasi/tagihan-kelompok/process', [RealisasiTagihanKelompokController::class, 'processRealisasi'])->name('realisasi.tagihanKelompok.process');

    //Realisasi Musyarokah
    Route::get('/realisasi-musyarakah', [RealisasiMusyarokahController::class, 'index']);
    Route::get('/realisasi-musyarakah-get-kelompok', [RealisasiMusyarokahController::class, 'getSetKelompok']);
    Route::get('/realisasi-musyarakah/getData', [RealisasiMusyarokahController::class, 'getData']);
    Route::post('/proses-realisasi-musyarakah', [RealisasiMusyarokahController::class, 'realisasiMusyarokah']);

    // setoran kerkelompok
    Route::get('/transaksi/setoran-perkelompok', [SetoranPerkelompokController::class, 'index']);
    Route::post('/transaksi/setoran-perkelompok/filter', [SetoranPerkelompokController::class, 'filter'])->name('setoranPerkelompok.filter');
    Route::post('/transaksi/setoran-perkelompok/proses/{code_kel}', [SetoranPerkelompokController::class, 'proses'])->name('setoranPerkelompok.proses');
    Route::get('/transaksi/setoran-perkelompok/cari-kelompok', [SetoranPerkelompokController::class, 'cari'])->name('setoranPerkelompok.getKelompok');


    // Transaksi Setoran Beda Hari
    Route::get('/transaksi/setoran-beda-hari', [SetoranBedaHariController::class, 'index']);
    Route::post('/transaksi/setoran-beda-hari/filter', [SetoranBedaHariController::class, 'filter'])->name('setoranBedaHari.filter');
    Route::post('/transaksi/setoran-beda-hari/proses/{code_kel}', [SetoranBedaHariController::class, 'proses'])->name('setoranBedaHari.proses');

    // Route::get('/transaksi/setoran-beda-hari/cari-kelompok', [SetoranBedaHariController::class, 'cari'])->name('cari.kelompok');

    Route::get('/transaksi/jurnal-masuk', [JurnalMasukController::class, 'index']);
    Route::get('/transaksi/jurnal-masuk/get-coa', [JurnalMasukController::class, 'getCoa'])->name('jurnalMasuk.getCoa');
    Route::post('/transaksi/jurnal-masuk/simpan', [JurnalMasukController::class, 'simpan'])->name('jurnalMasuk.simpan');


    Route::get('/transaksi/setoran-beda-hari/cari-kelompok', [SetoranBedaHariController::class, 'cari'])->name('setoranBedaHari.getKelompok');

    // report tunggakan

    // Route::get('/transaksi/setoran-beda-hari/cari-kelompok', [SetoranBedaHariController::class, 'cari'])->name('cari.kelompok');

    Route::get('/report/mutasi', [ReportMutasiController::class, 'index']);
    Route::get('/report/mutasi/cetak-pdf', [ReportMutasiController::class, 'cetakPdf']);
    Route::get('/report/mutasi/get-cif', [ReportMutasiController::class, 'getCif'])->name('reportMutasi.getCif');

    Route::get('/report/tunggakan', [ReportTunggakanController::class, 'index']);
    Route::get('/report/tunggakan/data', [ReportTunggakanController::class, 'data'])->name('reportTunggakan.data');
    Route::get('/report/tunggakan/export', [ReportTunggakanController::class, 'export'])->name('reportTunggakan.export');


    Route::get('/transaksi/pelunasan', [PelunasanController::class, 'index']);
    Route::get('/transaksi/pelunasan/cari-anggota', [PelunasanController::class, 'cari'])->name('pelunasan.cariAnggota');
    Route::get('/pelunasan/get-anggota', [PelunasanController::class, 'getAnggota'])->name('pelunasan.getAnggota');
    Route::post('/transaksi/pelunasan/proses', [PelunasanController::class, 'proses'])->name('pelunasan.proses');


    // PB Perkelompok
    Route::get('/transaksi/pemindahbukuan-perkelompok', [PemindahbukuanPerkelompokController::class, 'index']);
    Route::get('/transaksi/pemindahbukuan-perkelompok/cari-kelompok', [PemindahbukuanPerkelompokController::class, 'cari'])->name('pemindahbukuanPerkelompok.cariKelompok');
    Route::post('/transaksi/pemindahbukuan-perkelompok/filter', [PemindahbukuanPerkelompokController::class, 'filter'])->name('pemindahbukuanPerkelompok.filter');
    Route::post('/transaksi/pemindahbukuan-perkelompok/proses/{code_kel}', [PemindahbukuanPerkelompokController::class, 'proses'])->name('pemindahbukuanPerkelompok.proses');


    Route::get('/transaksi/pelunasan-kelompok', [PelunasanKelompokController::class, 'index']);
    Route::get('/transaksi/pelunasan-kelompok/cari-kelompok', [PelunasanKelompokController::class, 'cari'])->name('pelunasanKelompok.cariKelompok');
    Route::post('/transaksi/pelunasan-kelompok/filter', [PelunasanKelompokController::class, 'filter'])->name('pelunasanKelompok.filter');
    Route::post('/transaksi/pelunasan-kelompok/proses/{code_kel}', [PelunasanKelompokController::class, 'proses'])->name('pelunasanKelompok.proses');

    Route::get('/transaksi/jurnal-keluar', [JurnalKeluarController::class, 'index']);
    Route::post('/transaksi/jurnal-keluar/store', [JurnalKeluarController::class, 'store'])->name('jurnalKeluar.store');

    Route::get('/transaksi/jurnal-umum', [JurnalUmumController::class, 'index']);
    Route::get('/transaksi/jurnal-umum/get-coa', [JurnalUmumController::class, 'getCoa'])->name('jurnalUmum.getCoa');
    Route::post('/transaksi/jurnal-umum/', [JurnalUmumController::class, 'simpan'])->name('jurnalUmum.simpan');
    Route::post('/transaksi/jurnal-umum/cetak', [JurnalUmumController::class, 'cetak'])->name('jurnalUmum.cetak');

    Route::get('/transaksi/posting-jurnal', [PostingJurnalController::class, 'index'])->name('posting-jurnal.index');
    Route::post('/transaksi/posting-jurnal', [PostingJurnalController::class, 'posting'])->name('posting-jurnal.posting');

    Route::get('/laporan/hitung-shu', [HitungShuController::class, 'index'])->name('hitung-shu.index');
    Route::post('/laporan/hitung-shu', [HitungShuController::class, 'proses'])->name('hitung-shu.proses');


    // list jurnal
    Route::get('/new-report/list-jurnal', [ListJurnalController::class, 'index']);
    Route::post('/new-report/list-jurnal/get-transaksi', [ListJurnalController::class, 'getTransaksi'])->name('listJurnal.getTransaksi');
    Route::get('/new-report/list-jurnal/export-excel', [ListJurnalController::class, 'export']);



    Route::get('/report/mutasi-kas', [MutasiKasController::class, 'index']);
    Route::post('/report/mutasi-kas/get-transaksi', [MutasiKasController::class, 'getTransaksi'])->name('mutasiKas.getTransaksi');




    Route::get('/report/nominative-pembiayaan', [ReportNominativePembiayaanController::class, 'index']);
    Route::post('/report/nominative-pembiayaan/get-data', [ReportNominativePembiayaanController::class, 'getData'])->name('nominativePembiayaan.getData');
    Route::get('/report/nominative-pembiayaan/export', [ReportNominativePembiayaanController::class, 'export'])->name('nominativePembiayaan.export');


    Route::get('/report/ekuitas', [ReportEkuitasController::class, 'index']);
    Route::get('/report/ekuitas/export', [ReportEkuitasController::class, 'exportExcel'])->name('report.ekuitas.export');
    Route::post('/report/ekuitas', [ReportEkuitasController::class, 'getData'])->name('report.ekuitas');



    Route::get('/report/nominative-simpanan', [ReportNominativeSimpananController::class, 'index']);
    Route::post('/report/nominative-simpanan/get-data', [ReportNominativeSimpananController::class, 'getData'])->name('nominativeSimpanan.getData');
    Route::get('/report/nominative-simpanan/export', [ReportNominativeSimpananController::class, 'export'])->name('nominativeSimpanan.export');


    Route::get('/report/arus-kas', [ReportArusKasController::class, 'index'])->name('report.arus-kas.index');
    Route::post('/report/arus-kas/generate', [ReportArusKasController::class, 'generate'])->name('report.arus-kas.generate');


    Route::get('/report/neraca', [ReportNeracaController::class, 'index'])->name('report.neraca.index');
    Route::get('/report/neraca/export', [ReportNeracaController::class, 'exportExcel'])->name('report.neraca.export');


    Route::get('/pull-data', [PullDataController::class, 'index']);
    Route::post('/pull-data/proses', [PullDataController::class, 'data']);
    Route::delete('/pull-data/{id}', [PullDataController::class, 'destroy'])->name('pull-data.destroy');
    Route::get('/pull-data/suggest', [PullDataController::class, 'suggest'])->name('pull-data.suggest');
    Route::get('/pull-data/list', [PullDataController::class, 'list'])->name('pull-data.list');

    Route::get('/cs_mobcol', [ExportMobcolController::class, 'index']);
    Route::get('/tagihan/data', [ExportMobcolController::class, 'getData'])->name('csmobcol.data');
    Route::get('/export/cs', [ExportMobcolController::class, 'exportCs'])->name('export.cs');
    Route::get('/export/penarikan', [ExportMobcolController::class, 'exportPenarikan'])->name('export.penarikan');
    Route::get('/export/lebaran', [ExportMobcolController::class, 'exportLebaran'])->name('export.lebaran');
    Route::get('/export/lima', [ExportMobcolController::class, 'exportLima'])->name('export.lima');
    Route::get('/export/pelunasan', [ExportMobcolController::class, 'exportPelunasan'])->name('export.pelunasan');
    Route::get('/export/tunggakan', [ExportMobcolController::class, 'exportTunggakan'])->name('export.tunggakan');
    Route::get('/export/wo', [ExportMobcolController::class, 'exportWo'])->name('export.wo');

    Route::get('/setoran-bank', [SetoranBankController::class, 'index'])->name('setoran-bank.index');
    Route::get('/setoran-bank/data', [SetoranBankController::class, 'getData'])->name('setoran-bank.data');

    Route::get('/report-mobcol', [ReportMobcolController::class, 'index'])->name('report.index');
    Route::get('/report-mobcol/export/{type}', [ReportMobcolController::class, 'export'])->name('report.export');

    Route::get('/buku-besar', [BukuBesarController::class, 'index'])->name('buku-besar.index');
    Route::post('/buku-besar/proses', [BukuBesarController::class, 'proses'])->name('buku-besar.proses');
    Route::get('/buku-besar/download/{no_perkiraan}', [BukuBesarController::class, 'download'])->name('buku-besar.download');

    Route::get('/buku-besar/suggest', [BukuBesarController::class, 'suggest'])->name('buku-besar.suggest');

    Route::get('/report/ppap', [ReportPpapController::class, 'index']);
    Route::post('/report/ppap/cari', [ReportPpapController::class, 'cari'])->name('report.ppap.cari');
    Route::get('/report/ppap/export/pdf', [ReportPpapController::class, 'exportPdf'])->name('report.ppap.export.pdf');
    Route::get('/report/ppap/export/excel', [ReportPpapController::class, 'exportExcel'])->name('report.ppap.export.excel');


});

// untuk Al
Route::group(['middleware' => ['auth', 'role:2']], function () {
    Route::get('/al', [AlController::class, 'index']);

    Route::get('/al/approval-pengajuan', [ApprovalPengajuanController::class, 'index']);
    Route::get('/al/approve/get_pengajuan', [ApprovalPengajuanController::class, 'get_pengajuan'])->name('approve.get_pengajuan');
    Route::get('/al/approval-pengajuan/detail/{no_anggota}', [ApprovalPengajuanController::class, 'get_pengajuan_detail']);
    Route::post('/al/approval-pengajuan/cari-ktp', [ApprovalPengajuanController::class, 'getKtp']);

    Route::put('/al/approval-pengajuan/update/{no_anggota}', [ApprovalPengajuanController::class, 'update_pengajuan']);
    Route::post('/al/approval-pengajuan/approve/{no_anggota}', [ApprovalPengajuanController::class, 'approvePengajuan']);
    Route::post('/al/approval-pengajuan/approve-checkbox', [ApprovalPengajuanController::class, 'approveCheckbox']);
    Route::post('/al/approval-pengajuan/batal-checkbox', [ApprovalPengajuanController::class, 'batalCheckbox']);

    Route::get('/al/approval-pengajuan/ajukan-kembali', [ApprovalPengajuanController::class, 'ajukanKembali']);

    Route::get('/ajax/cif-batal', [ApprovalPengajuanController::class, 'getCifBatal'])->name('ajax.cif.batal');
    Route::post('/al/approval-pengajuan/ajukan-kembali/proses', [ApprovalPengajuanController::class, 'prosesAjukanKembali'])->name('ajukan.kembali.proses');

    Route::get('/al/approval-pengajuan/hapus', [ApprovalPengajuanController::class, 'hapusPengajuan']);
    Route::get('/ajax/cif-hapus', [ApprovalPengajuanController::class, 'getCifHapus'])->name('ajax.cif.hapus');
    Route::post('/al/approval-pengajuan/hapus/proses', [ApprovalPengajuanController::class, 'prosesHapusPengajuan'])->name('hapus.proses');

    Route::get('/al/approval-pengajuan/turun-plafond', [ApprovalPengajuanController::class, 'turunPlafond']);
    Route::get('/ajax/cif-turun-plafond', [ApprovalPengajuanController::class, 'getCifTurunPlafond'])->name('ajax.turun_plafond');
    Route::post('/al/approval-pengajuan/turun-plafond/proses', [ApprovalPengajuanController::class, 'prosesTurunPlafond'])->name('turun.plafond.proses');

});

// untuk AH
Route::group(['middleware' => ['auth', 'role:3']], function () {
    Route::get('/ah', [AhController::class, 'index']);

});

// untuk KP
Route::group(['middleware' => ['auth', 'role:4']], function () {
    Route::get('/kp', [KpController::class, 'index']);

Route::get('/buku-besar-kp', [BukuBesarKpController::class, 'index'])->name('buku-besar-kp.index');
Route::post('/buku-besar-kp/proses', [BukuBesarKpController::class, 'proses'])->name('buku-besar-kp.proses');
Route::get('/buku-besar-kp/suggest', [BukuBesarKpController::class, 'suggest'])->name('buku-besar-kp.suggest');
Route::get('/buku-besar-kp/export/list', [BukuBesarKpController::class, 'list'])->name('buku-besar-kp.export.list');
Route::get('/buku-besar-kp/download/{id}', [BukuBesarKpController::class, 'download'])->name('buku-besar-kp.download');

Route::get('/list-jurnal-kp', [ListJurnalKpController::class, 'index']);
Route::post('/list-jurnal-kp/get-transaksi', [ListJurnalKpController::class, 'getTransaksi'])->name('listJurnalKp.getTransaksi');
Route::get('/list-jurnal-kp/export-excel', [ListJurnalKpController::class, 'export']);


Route::get('/nominative-pembiayaan-kp', [ReportNominatifPembiayaanKpController::class, 'index']);
Route::post('/nominative-pembiayaan-kp/get-data', [ReportNominatifPembiayaanKpController::class, 'getData'])->name('nominativePembiayaan-kp.getData');
Route::get('/nominative-pembiayaan-kp/export', [ReportNominatifPembiayaanKpController::class, 'export'])->name('nominativePembiayaan-kp.export');

Route::get('/report/neraca-kp', [ReportNeracaKpController::class, 'index'])->name('report.neraca-kp.index');
Route::get('/report/neraca-kp/export', [ReportNeracaKpController::class, 'exportExcel'])->name('report.neraca-kp.export');

Route::get('/transaksi/posting-jurnal-kp', [PostingJurnalKpController::class, 'index'])->name('posting-jurnal-kp.index');
Route::post('/transaksi/posting-jurnal-kp', [PostingJurnalKpController::class, 'posting'])->name('posting-jurnal-kp.posting');

});

