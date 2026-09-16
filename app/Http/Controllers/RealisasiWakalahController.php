<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\temp_akad_mus;
use App\Repositories\Contracts\KelompokRepositoryInterface;

class RealisasiWakalahController extends BaseController
{
    public function __construct(
        protected KelompokRepositoryInterface $kelompokRepository
    ) {
    }

    public function index()
    {
        $menus = $this->getMenus();
        $pembiayaan = DB::table('pembiayaan')
        ->selectRaw('SUM(os - saldo_margin) as os, COUNT(cif) as noa')
        ->first();
        $title = 'Dashboard';

        return view('admin.realisasi_wakalah.index',compact('menus','pembiayaan','title'));

    }

    public function cariKelompok(Request $request)
    {
        $kelompok = $this->kelompokRepository->search($request->q, Auth::user()->unit, 20);

        return response()->json($kelompok);
    }

    public function getData(Request $request)
    {
        $query = temp_akad_mus::query()
        ->join('kelompok', 'temp_akad_mus.code_kel', '=', 'kelompok.code_kel')
        ->where('status_app', 'APPROVE')
        ->select(
            'temp_akad_mus.*',
            'kelompok.nama_kel',
        );



    if ($request->kode_kelompok) {
        $query->where('kelompok.code_kel', 'LIKE', '%' . $request->kode_kelompok . '%');
    }

    if ($request->tanggal_realisasi) {
        $query->where('tgl_wakalah', $request->tanggal_realisasi);
    }


    $data = $query->get();

    return response()->json($data);

    }
    public function realisasiWakalah(Request $request)
    {

            $ids = $request->ids;


        if (empty($ids) || !is_array($ids)) {
            return response()->json(['message' => 'Tidak ada data yang dipilih.'], 400);
        }


        temp_akad_mus::whereIn('cif', $ids)->update(['status_app' => 'MURAB']);

        return response()->json(['message' => 'Realisasi Wakalah berhasil dilakukan.']);
    }

}
