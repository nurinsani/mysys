<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\temp_akad_mus;
use App\Models\Pembiayaan;
use App\Http\Requests\Realisasi\ProsesRealisasiMusyarokahRequest;
use App\Services\Realisasi\RealisasiMusyarokahService;
use App\Repositories\Contracts\KelompokRepositoryInterface;
use Carbon\Carbon;


class RealisasiMusyarokahController extends BaseController
{
    public function __construct(
        protected RealisasiMusyarokahService $realisasiMusyarokahService,
        protected KelompokRepositoryInterface $kelompokRepository
    ) {
    }

    public function index()
    {
        $menus = $this->getMenus();
        $pembiayaan = DB::table('pembiayaan')
        ->selectRaw('SUM(os - saldo_margin) as os, COUNT(cif) as noa')
        ->first();
        $title = 'Setoran Lima Persen';

        return view('admin.realisasi_musyarokah.index',compact('menus','pembiayaan','title'));

    }

    public function getData(Request $request)
    {
        $query = temp_akad_mus::query()
        ->join('kelompok', 'temp_akad_mus.code_kel', '=', 'kelompok.code_kel')
        ->where('status_app', 'MUSYARAKAH')
        ->select(
            'temp_akad_mus.*',
            'kelompok.nama_kel',
        );



    if ($request->kode_kelompok) {
        $query->where('kelompok.code_kel', 'LIKE', '%' . $request->kode_kelompok . '%');
    }

    if ($request->tanggal_realisasi) {
        $query->where('tgl_akad', $request->tanggal_realisasi);
    }


    $data = $query->get();

    return response()->json($data);

    }
    public function realisasiMusyarokah(ProsesRealisasiMusyarokahRequest $request)
    {
        $result = $this->realisasiMusyarokahService->realisasikan($request->validated('ids'));

        return response()->json($result['body'], $result['status']);
    }





    public function getSetKelompok(Request $request)
    {
        $kelompok = $this->kelompokRepository->search($request->q, auth()->user()->unit, 20);

        return response()->json($kelompok);
    }

}

