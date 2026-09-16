<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pembiayaan;
use App\Models\Kelompok;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminController extends BaseController
{
    public function index()
    {
        $menus = $this->getMenus();
        $unit = Auth::user()->unit;

        $pembiayaan = DB::table('pembiayaan')
            ->selectRaw('SUM(os - saldo_margin) as os, COUNT(cif) as noa')
            ->first();

        $jumlahKelompok = Kelompok::count();

        $jumlahPenunggak = DB::table('tunggakan')
            ->select('cif')
            ->groupBy('cif')
            ->havingRaw('SUM(kredit - debet) > 0')
            ->get()
            ->count();

        $totalSimpanan = DB::table('simpanan')->selectRaw('COALESCE(SUM(kredit - debet), 0) as saldo')->value('saldo')
            + DB::table('simpanan_pokok')->selectRaw('COALESCE(SUM(kredit - debet), 0) as saldo')->value('saldo')
            + DB::table('simpanan_wajib')->selectRaw('COALESCE(SUM(kredit - debet), 0) as saldo')->value('saldo');

        // Dibaca dari hasil terakhir proses "Hitung SHU" resmi (menu Hitung SHU), BUKAN
        // dihitung ulang di sini — jalankanHitungShu() me-reset & rebuild seluruh
        // tabel_master, tidak aman dipicu tiap dashboard dibuka.
        $shuYtd = DB::table('tabel_master')
            ->where('unit', $unit)
            ->where('kode_rekening', '3902000')
            ->value('saldo_akhir');

        $transaksiHariIni = DB::table('tabel_transaksi')
            ->where('unit', $unit)
            ->whereDate('tanggal_transaksi', today())
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(debet), 0) as total_debet, COALESCE(SUM(kredit), 0) as total_kredit')
            ->first();

        $title = 'Dashboard';

        return view('admin.index', compact('menus', 'pembiayaan', 'jumlahKelompok', 'jumlahPenunggak', 'totalSimpanan', 'shuYtd', 'transaksiHariIni', 'title'));
    }
}
