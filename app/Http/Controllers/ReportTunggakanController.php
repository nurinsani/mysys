<?php

namespace App\Http\Controllers;

use App\Exports\ReportTunggakanExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportTunggakanController extends BaseController
{
    public function data()
    {
        $unit = Auth::user()->unit;
        $tunggakan = DB::table('pembiayaan')
            ->join('kelompok', 'pembiayaan.code_kel', '=', 'kelompok.code_kel')
            ->join('ao', 'pembiayaan.cao', '=', 'ao.cao')
            ->leftJoin('tunggakan', 'pembiayaan.cif', '=', 'tunggakan.cif')
            ->where('pembiayaan.unit', $unit)
            ->select(
                'pembiayaan.unit',
                'pembiayaan.nama as nama_anggota',
                'pembiayaan.cif',
                'ao.nama_ao',
                'kelompok.nama_kel as nama_kelompok',
                DB::raw('COALESCE(SUM(tunggakan.kredit - tunggakan.debet), 0) as total_tunggakan'),
                DB::raw("
                    GREATEST(
                        COUNT(DISTINCT CASE WHEN tunggakan.kredit > 0 THEN tunggakan.tgl_tunggak END) 
                        - 
                        COUNT(DISTINCT CASE WHEN tunggakan.debet > 0 THEN tunggakan.tgl_tunggak END),
                        0
                    ) as ft
                ")
            )
            ->groupBy(
                'pembiayaan.unit',
                'pembiayaan.nama',
                'pembiayaan.cao',
                'pembiayaan.cif',
                'kelompok.nama_kel')
            ->havingRaw('total_tunggakan > 0')
            ->get();

        return datatables()
            ->of($tunggakan)
            ->addIndexColumn()
            ->rawColumns([])
            ->make(true);
    }

    public function index()
    {
        $title = 'Report Tunggakan';
        $menus = $this->getMenus();
        return view('admin.report_tunggakan.index', compact('title', 'menus'));
    }

    public function export()
    {
        return Excel::download(new ReportTunggakanExport, 'tunggakan.xlsx');
    }
}
