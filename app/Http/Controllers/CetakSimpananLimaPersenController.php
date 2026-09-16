<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class CetakSimpananLimaPersenController extends BaseController
{
    public function index()
    {
        $menus = $this->getMenus();
        $title = 'Cetak Simpanan 5%';

        return view('admin.cetak_simpanan_5_persen.index', compact('menus', 'title'));
    }

    public function filter(Request $request)
    {
        $code_kel = $request->input('code_kel');
        $tgl_akad = $request->input('tgl_akad');

        $data = DB::table('temp_akad_mus')
            ->join('anggota', 'temp_akad_mus.no_anggota', '=', 'anggota.no')
            ->select(
                'temp_akad_mus.*',
                'anggota.*',
                'anggota.nama as nama_anggota'
            )
            ->where('temp_akad_mus.code_kel', $code_kel)
            ->whereDate('temp_akad_mus.tgl_akad', $tgl_akad)
            ->get();

        return response()->json(['data' => $data]);
    }

    public function cetakPDF(Request $request)
    {
        $code_kel = $request->input('code_kel');
        $tgl_akad = $request->input('tgl_akad');

        $data = DB::table('temp_akad_mus')
            ->join('anggota', 'temp_akad_mus.no_anggota', '=', 'anggota.no')
            ->select(
                'temp_akad_mus.*',
                'anggota.*',
                'anggota.nama as nama_anggota',
            )
            ->where('temp_akad_mus.code_kel', $code_kel)
            ->whereDate('temp_akad_mus.tgl_akad', $tgl_akad)
            ->get();
        
        $data_kel = DB::table('temp_akad_mus')
            ->join('kelompok', 'temp_akad_mus.code_kel', '=', 'kelompok.code_kel')
            ->join('ao', 'kelompok.cao', '=', 'ao.cao')
            ->select(
                'temp_akad_mus.tgl_akad',
                'kelompok.code_kel',
                'kelompok.nama_kel',
                'ao.nama_ao',
                'ao.kode_unit'
            )
            ->where('temp_akad_mus.code_kel', $code_kel)
            ->whereDate('temp_akad_mus.tgl_akad', $tgl_akad)
            ->first();

        if ($data->isEmpty()) {

            alert()->error('Oops!', 'Data tidak di temukan!');
            return redirect()->back();
        }

        $pdf = PDF::loadView('admin.cetak_simpanan_5_persen.pdf', compact('data', 'tgl_akad', 'data_kel'))
        ->setPaper('a4', 'portrait');

        return $pdf->stream('Simpanan 5 persen - ' . $tgl_akad . '.pdf');
    }

}
