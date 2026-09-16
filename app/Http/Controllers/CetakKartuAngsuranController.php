<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class CetakKartuAngsuranController extends BaseController
{
    public function index()
    {
        $menus = $this->getMenus();
        $title = 'Cetak Kartu Angsuran';

        return view('admin.cetak_kartu_angsuran.index', compact('menus', 'title'));
    }

    public function filter(Request $request)
    {
        $code_kel = $request->input('code_kel');
        $tgl_wakalah = $request->input('tgl_wakalah');

        $data = DB::table('pembiayaan')
            ->join('anggota', 'pembiayaan.no_anggota', '=', 'anggota.no')
            ->select(
                'pembiayaan.*',
                'anggota.*',
                'anggota.nama as nama_anggota'
            )
            ->where('pembiayaan.code_kel', $code_kel)
            ->whereDate('pembiayaan.tgl_wakalah', $tgl_wakalah)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function cetakPDF(Request $request)
    {
        $code_kel = $request->input('code_kel');
        $tgl_wakalah = $request->input('tgl_wakalah');

        $data = DB::table('pembiayaan')
            ->join('anggota', 'pembiayaan.no_anggota', '=', 'anggota.no')
            ->join('kelompok', 'pembiayaan.code_kel', '=', 'kelompok.code_kel')
            ->select(
                'pembiayaan.*',
                'anggota.*',
                'anggota.nama as nama_anggota',
                'kelompok.*'
            )
            ->where('pembiayaan.code_kel', $code_kel)
            ->whereDate('pembiayaan.tgl_wakalah', $tgl_wakalah)
            ->get();

        if ($data->isEmpty()) {

            alert()->error('Oops!', 'Data tidak di temukan!');
            return redirect()->back();
        }

        $pdf = PDF::loadView('admin.cetak_kartu_angsuran.pdf', compact('data', 'tgl_wakalah'))
        ->setPaper('a4', 'portrait');

        return $pdf->stream('Cetak Kartu Angsuran-' . $tgl_wakalah . '.pdf');
    }
}
