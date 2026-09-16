<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MutasiKasController extends BaseController
{
    public function index()
    {
        $title = 'Mutasi Kas';
        $menus = $this->getMenus();

        return view('admin.mutasi_kas.index', compact('menus', 'title'));
    }

    public function getTransaksi(Request $request)
    {
        $unit = Auth::user()->unit;
        $tanggalAwal = $request->tanggal_awal;
        $tanggalAkhir = $request->tanggal_akhir;

        $data = DB::table('tabel_transaksi')
            ->where('unit', $unit)
            ->when($tanggalAwal && $tanggalAkhir, function ($query) use ($tanggalAwal, $tanggalAkhir) {
                $query->whereBetween('tanggal_transaksi', [$tanggalAwal, $tanggalAkhir]);
            })
            ->select(
                'kode_rekening',
                'keterangan_transaksi',
                'tanggal_transaksi',
                'debet',
                'kredit'
            )
            ->get();

        $saldo = 0;
        $data = $data->map(function ($row) use (&$saldo) {
            $saldo += ($row->debet - $row->kredit);
            $row->saldo = $saldo;
            return $row;
        });


        return response()->json(['data' => $data]);
    }
}
