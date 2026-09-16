<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\pull_data;
use App\Services\PullData\PullDataService;
use Illuminate\Support\Facades\DB;

class PullDataController extends BaseController
{
    public function __construct(protected PullDataService $pullDataService)
    {
    }

    public function index()
    {
        $menus = $this->getMenus();
        $pembiayaan = DB::table('pull_data')
        ->selectRaw('SUM(os - saldo_margin) as os, COUNT(cif) as noa')
        ->first();
        $data = DB::table('pull_data')->get();
        $title = 'Pull Data';

        return view('admin.pull-data.index',compact('menus','pembiayaan','title','data'));

    }

    public function data(Request $request)
    {
        $result = $this->pullDataService->pull(
            $request->jenis_pull,
            $request->jenis_transaksi,
            $request->kode_kelompok,
            $request->tanggal_tagih,
            $request->nominal !== null ? (float) $request->nominal : null
        );

        return response()->json($result['body'], $result['status']);
    }


public function destroy($id)
{
    $item = pull_data::findOrFail($id);
    $item->delete();


                       $deleted = DB::table('pull_data')->get();

                        return response()->json([
                            'success' => true,
                            'message' => 'Pull Data 5% sukses',
                            'data'    => $deleted
                        ]);
}

public function suggest(Request $request)
{
    $q = $request->get('q');
    $data = DB::table('anggota')
        ->leftJoin('kelompok', 'kelompok.code_kel', '=', 'anggota.kode_kel')
        ->select('anggota.CIF as cif', 'anggota.nama as nama', 'kelompok.nama_kel')
        ->where('anggota.CIF', 'like', $q.'%')
        ->orWhere('anggota.nama', 'like', $q.'%')
        ->limit(10)
        ->get();

    if ($data->isEmpty()) {
        $data = DB::table('pembiayaan')
            ->leftJoin('kelompok', 'kelompok.code_kel', '=', 'pembiayaan.code_kel')
            ->select('pembiayaan.cif as cif', 'pembiayaan.nama as nama', 'kelompok.nama_kel')
            ->where('pembiayaan.cif', 'like', $q.'%')
            ->orWhere('pembiayaan.nama', 'like', $q.'%')
            ->limit(10)
            ->get();
    }

    if ($data->isEmpty()) {
        $data = DB::table('anggota')
            ->leftJoin('kelompok', 'kelompok.code_kel', '=', 'anggota.kode_kel')
            ->select('anggota.kode_kel as cif', 'kelompok.nama_kel as nama', 'kelompok.nama_kel')
            ->where('anggota.kode_kel', 'like', $q.'%')
            ->orWhere('kelompok.nama_kel', 'like', $q.'%')
            ->groupBy('anggota.kode_kel', 'kelompok.nama_kel')
            ->limit(10)
            ->get();
    }

    return response()->json($data);
}

public function list()
{
    $data = DB::table('pull_data')->get();

    return response()->json(['data' => $data]);
}


}
