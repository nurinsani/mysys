<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use RealRashid\SweetAlert\Facades\Alert;


class CetakCsWoController extends BaseController
{
    public function index()
    {
        $menus = $this->getMenus();
        $title = 'Cetak Cs';

        return view("admin.cetak_cs_wo.index", compact("menus", "title"));
    }

    public function cariAo()
    {

        $data = DB::table('ao')
            ->where('kode_unit',Auth::user()->unit)
            ->select('cao as id', 'nama_ao as text')
            ->get();

        return response()->json($data);
    }

    public function pdfCsWo(Request $request)
    {
        $menus = $this->getMenus();
        $title = 'Cetak Cs';
        $tanggal = $request->input('tanggalTagih');
        $hari = \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('l');
        $kodeAo = $request->input('kodeAo');

        if (empty($kodeAo) || empty($tanggal)) {
            Alert::warning('Peringatan', 'Kode AO tidak boleh kosong.');
            return redirect()->back();
        }

        $cs_anggota = DB::table('pembiayaan')
            ->join('kelompok', 'pembiayaan.code_kel', '=', 'kelompok.code_kel')
            ->join('ao', 'ao.cao', '=', 'pembiayaan.cao')
            ->join('branch', 'branch.kode_branch', '=', 'pembiayaan.unit')
            ->leftjoin('simpanan', 'simpanan.cif', '=', 'pembiayaan.cif')
            ->leftjoin('tunggakan', 'tunggakan.cif', '=', 'pembiayaan.cif')
            ->where('pembiayaan.cao', $kodeAo)
            ->where('hari', $hari)
            ->select(DB::raw('no_anggota, nama,plafond,saldo_margin,os,angsuran,
             bulat,hari,nama_kel,nama_ao,branch.unit as ap,branch.alamat as alamat_ap,
             pembiayaan.code_kel as code_kel,run_tenor,ke,pembiayaan.cif as cif,
             sum(simpanan.kredit-simpanan.debet) as twm,sum(tunggakan.kredit-tunggakan.debet) as tunggakan'))
            ->groupBy('pembiayaan.cif')

            ->get();
        $cs_anggota = $cs_anggota->groupBy('code_kel');

        if ($cs_anggota->isEmpty()) {
            Alert::error('Error', 'Data tidak ditemukan.');
            return redirect()->back();
        }

        $data = [];
    foreach ($cs_anggota as $kelompokCode => $anggota) {
        $totals = [
            'plafond' => $anggota->sum('plafond'),
            'saldo_margin' => $anggota->sum('saldo_margin'),
            'os' => $anggota->sum('os'),
            'angsuran' => $anggota->sum('angsuran'),
            'bulat' => $anggota->sum('bulat'),
            'twm' => $anggota->sum('twm'),
            'tunggakan' => $anggota->sum('tunggakan'),
        ];

        $data[] = [
            'kelompok' => [
                'kode_kelompok' => $kelompokCode,
                'nama_kelompok' => $anggota->first()->nama_kel,
                'nama_petugas' => $anggota->first()->nama_ao,
                'area_pemasaran' => $anggota->first()->ap,
                'alamat' => $anggota->first()->alamat_ap,
                'hari' => $hari,
                'tanggal' => \Carbon\Carbon::parse($tanggal)->locale('id')->translatedFormat('d F Y'),
            ],
            'anggota' => $anggota,
            'totals' => $totals,
        ];
    }

    $pdf = Pdf::loadView('admin.cetak_cs_wo.pdfcs', compact('data'))->setPaper('a4', 'landscape');
    return view('admin.cetak_cs_wo.framePdf', compact('pdf', 'data', 'menus', 'title'));
    }
}
