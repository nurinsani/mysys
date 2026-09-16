<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\Realisasi\RealisasiMurabahahService;
use App\Repositories\Contracts\KelompokRepositoryInterface;
use Carbon\Carbon;

class RealisasiMurabahahController extends BaseController
{
    public function __construct(
        protected RealisasiMurabahahService $realisasiMurabahahService,
        protected KelompokRepositoryInterface $kelompokRepository
    ) {
    }

    public function index()
    {
        $menus = $this->getMenus();
        $title = 'Realisasi Murabahah';

        return view("admin.realisasi_murabahah.index", compact("menus", "title"));
    }

    public function cariKelompok(Request $request)
    {
        $kelompok = $this->kelompokRepository->search($request->q, Auth::user()->unit, 20);

        return response()->json($kelompok);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'kode_kel' => 'required|string',
            'tgl_akad' => 'required|date',
            'unit' => 'required|string'
        ]);

        $records = DB::table('temp_akad_mus')
            ->leftJoin('kelompok', 'temp_akad_mus.code_kel', '=', 'kelompok.code_kel')
            ->where('temp_akad_mus.code_kel', $validated['kode_kel'])
            ->where('temp_akad_mus.tgl_akad', $validated['tgl_akad'])
            ->where('temp_akad_mus.unit', $validated['unit'])
            ->select(
                'temp_akad_mus.*',
                'kelompok.nama_kel AS nama_kelompok'
            )
            ->get();

        return response()->json($records);
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'cifs' => 'required|array',
            'kode_kel' => 'required|string',
            'tgl_akad' => 'required|date',
            'unit' => 'required|string',
            'id' => 'required|string',
            'param_tanggal' => 'required|date',
        ]);

        $result = $this->realisasiMurabahahService->updateStatus($validated);

        return response()->json($result['body'], $result['status']);
    }
}
