<?php

namespace App\Http\Controllers;

use App\Models\PembiayaanDetail;
use App\Models\simpanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\PembiayaanDetailSeeder;

class ReportMutasiController extends BaseController
{
    public function index()
    {
        $title = 'Repoer Mutasi';
        $menus = $this->getMenus();
        return view('admin.report_mutasi.index', compact('title', 'menus'));
    }

    public function getCif(Request $request)
    {
        $cari = $request->input('cari');
        
        $results = DB::table('pembiayaan')
            ->select('cif', 'nama')
            ->where('cif', 'like', '%'.$cari.'%')
            ->orWhere('no_anggota', 'like', '%'.$cari.'%')
            ->limit(10)
            ->get();

        return response()->json($results);
    }

    public function cetakPdf(Request $request)
    {
        $cif = $request->query('cif');
        $jenis = $request->query('jenis');

        $anggota = DB::table('pembiayaan')
            ->where('cif', $cif)
            ->orWhere('no_anggota', $cif)
            ->first();
        
        $mutasiSimpanan = simpanan::where('cif', $cif)
        ->orderBy('buss_date', 'asc')
        ->get();

        $mutasiKartuAngsuran = PembiayaanDetail::where('cif', $cif)
        ->orderBy('tgl_bayar', 'asc')
        ->get();

        if (!$anggota) {
            return abort(404, 'Data tidak ditemukan');
        }

        $view = $jenis == 1 ? 'admin/report_mutasi/cetak_simpanan' : 'admin/report_mutasi/cetak_kartu_angsuran';

        $pdf = Pdf::loadView($view, compact('anggota', 'mutasiSimpanan', 'mutasiKartuAngsuran'));
        return $pdf->stream('mutasi_' . $cif . '.pdf');
    }
}
