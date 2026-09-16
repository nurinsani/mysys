<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ListJurnalExport;


class ListJurnalKpController extends BaseController
{
    public function index()
    {
        $roleId = auth()->user()->role_id;
        $title = 'List Jurnal';
         $menus = Menu::whereNull('parent_id')
            ->where(function ($q) use ($roleId) {
                $q->where('role_id', $roleId)->orWhereNull('role_id');
            })
            ->with(['children' => function ($q) use ($roleId) {
                $q->where('role_id', $roleId)->orWhereNull('role_id');
            }])
            ->orderBy('order')
            ->get();

        return view('kp.list_jurnal.index', compact('menus', 'title'));
    }

    public function getTransaksi(Request $request)
    {
        $unit = Auth::user()->unit;
        $tanggalAwal = $request->tanggal_awal;
        $tanggalAkhir = $request->tanggal_akhir;

        $data = DB::table('tabel_transaksi')
            ->where('keterangan_posting', 'post')
            ->when($tanggalAwal && $tanggalAkhir, function ($query) use ($tanggalAwal, $tanggalAkhir) {
                $query->whereBetween('tanggal_transaksi', [$tanggalAwal, $tanggalAkhir]);
            })
            ->select(
                'unit',
                DB::raw('MIN(tanggal_transaksi) as tanggal_awal'),
                DB::raw('MAX(tanggal_transaksi) as tanggal_akhir'),
                DB::raw('SUM(debet) as total_debet'),
                DB::raw('SUM(kredit) as total_kredit')
            )
            ->groupBy('unit')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function export(Request $request)
    {
        $unit = $request->unit;
        $awal = $request->tanggal_awal;
        $akhir = $request->tanggal_akhir;

        return Excel::download(new ListJurnalExport($unit, $awal, $akhir), 'list_jurnal_'.$unit.'.xlsx');
    }

}
