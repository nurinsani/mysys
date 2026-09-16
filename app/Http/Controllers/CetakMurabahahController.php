<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class CetakMurabahahController extends BaseController
{
    public function index()
    {
        $menus = $this->getMenus();
        $title = 'Cetak Murabahah';

        return view('admin.cetak_murabahah.index', compact('menus', 'title'));
    }

    public function filter(Request $request)
    {
        $code_kel = $request->input('code_kel');
        $tgl_murab = $request->input('tgl_murab');

        $data = DB::table('temp_akad_mus')
            ->join('anggota', 'temp_akad_mus.no_anggota', '=', 'anggota.no')
            ->select(
                'temp_akad_mus.tgl_murab',
                'temp_akad_mus.code_kel',
                'temp_akad_mus.tenor',
                'anggota.cif',
                'anggota.ktp',
                'anggota.nama as nama_anggota'
            )
            ->where('temp_akad_mus.code_kel', $code_kel)
            ->whereDate('temp_akad_mus.tgl_murab', $tgl_murab)
            ->get();

        if ($data->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan!'
            ]);
        }
    
        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function cetakPDF(Request $request)
    {
        $code_kel = $request->input('code_kel');
        $tgl_murab = $request->input('tgl_murab');

        $data = DB::table('temp_akad_mus')
            ->join('anggota', 'temp_akad_mus.no_anggota', '=', 'anggota.no')
            ->join('kelompok', 'temp_akad_mus.code_kel', '=', 'kelompok.code_kel')
            ->join('ao', 'kelompok.cao', '=', 'ao.cao')
            ->join('mm', 'ao.atasan', '=', 'mm.nik')
            ->select(
                'temp_akad_mus.*',
                'anggota.*',
                'anggota.nama as nama_anggota',
                'mm.nama as nama_mm',
                'mm.jabatan',
            )
            ->where('temp_akad_mus.code_kel', $code_kel)
            ->whereDate('temp_akad_mus.tgl_murab', $tgl_murab)
            ->get();

        if ($data->isEmpty()) {

            alert()->error('Oops!', 'Data tidak di temukan!');
            return redirect()->back();
        }

        $pdf = PDF::loadView('admin.cetak_murabahah.pdf', compact('data', 'tgl_murab'))
        ->setPaper('a4', 'portrait');

        return $pdf->stream('Murabahah-' . $tgl_murab . '.pdf');
    }
}
