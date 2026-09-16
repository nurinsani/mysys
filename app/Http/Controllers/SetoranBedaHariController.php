<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Setoran\FilterSetoranBedaHariRequest;
use App\Services\Setoran\SetoranBedaHariService;
use App\Repositories\Contracts\KelompokRepositoryInterface;

class SetoranBedaHariController extends BaseController
{
    public function __construct(
        protected SetoranBedaHariService $setoranBedaHariService,
        protected KelompokRepositoryInterface $kelompokRepository
    ) {
    }

    public function index()
    {
        $title = 'Setoran Beda Hari';
        $menus = $this->getMenus();
        return view('admin.setoran_beda_hari.index', compact('title', 'menus'));
    }

    public function cari(Request $request)
    {
        $cari = $request->input('cari');

        $results = $this->kelompokRepository->search($cari, Auth::user()->unit, 10);

        return response()->json($results);
    }

    public function filter(FilterSetoranBedaHariRequest $request)
    {
        $code_kel = $request->validated('code_kel');

        $get_kelompok = DB::table('kelompok')
            ->where('code_kel', $code_kel)
            ->first();

        if (!$get_kelompok) {
            return response()->json(['message' => 'Kamu belum pilih kelompok'], 404);
        }

        $get_anggota = DB::table('anggota')
            ->join('pembiayaan', 'anggota.no', '=', 'pembiayaan.no_anggota')
            ->where('pembiayaan.code_kel', $code_kel)
            ->where('pembiayaan.run_tenor', '<', DB::raw('pembiayaan.tenor')) // anggota yang masih memiliki angsuran
            ->select(
                'anggota.*',
                'pembiayaan.*',
            )
            ->get();

        return response()->json([
            'kelompok' => $get_kelompok,
            'anggota' => $get_anggota,
        ]);
    }

    public function proses($code_kel)
    {
        $result = $this->setoranBedaHariService->prosesSetoran(
            $code_kel,
            request()->input('pilih_anggota', []),
            request()->input('input_nyata_setor', []),
            request()->input('input_debet', [])
        );

        return response()->json($result['body'], $result['status']);
    }
}
