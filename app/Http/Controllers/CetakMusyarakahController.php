<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CetakMusyarakahController extends BaseController
{
    public function index()
    {
        $menus = $this->getMenus();
        $title = 'Cetak Musyarakah';

        return view("admin.cetak_musyarakah.index", compact("menus", "title"));
    }

    public function hasil(Request $request)
    {
        $request->validate([
            'tanggal_cetak' => 'required|date_format:Y-m-d',
        ]);

        $tanggalCetak = \Carbon\Carbon::createFromFormat('Y-m-d', $request->tanggal_cetak)->format('Y-m-d');

        $query = DB::table('temp_akad_mus')
            ->leftJoin('branch', 'branch.kode_branch', '=', 'temp_akad_mus.unit')
            ->whereDate('temp_akad_mus.tgl_akad', $tanggalCetak);

        if ($request->filled('unit') && $request->unit !== 'all') {
            $query->where('temp_akad_mus.unit', $request->unit);
        }

        $results = $query->select(
                'temp_akad_mus.unit as kode_unit',
                DB::raw('COALESCE(branch.unit, temp_akad_mus.unit) as nama_unit'),
                DB::raw('COUNT(temp_akad_mus.cif) as total_noa'),
                DB::raw('SUM(temp_akad_mus.plafond) as total_plafond'),
                DB::raw('DATE(temp_akad_mus.tgl_akad) as tgl_cetak')
            )
            ->groupBy('temp_akad_mus.unit', 'branch.unit', DB::raw('DATE(temp_akad_mus.tgl_akad)'))
            ->orderBy('temp_akad_mus.unit', 'asc')
            ->get();

        if ($results->isEmpty()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data yang ditemukan untuk tanggal tersebut.',
                ]);
            }

            alert()->error('Oops!', 'Tidak ada data yang ditemukan untuk tanggal tersebut.');
            return redirect()->back();
        }

        $data = $results->map(function ($item) use ($tanggalCetak) {
            return [
                'kode_unit' => $item->kode_unit,
                'nama_unit' => $item->nama_unit ?? '-',
                'total_noa' => (int) $item->total_noa,
                'total_plafond' => (float) $item->total_plafond,
                'formatted_plafond' => number_format((float) $item->total_plafond, 0, ',', '.'),
                'tgl_cetak' => $tanggalCetak,
                'print_url' => route('pdf.generateMusyarakah', [
                    'feature' => 'cetak_musyarakah',
                    'date' => $tanggalCetak,
                    'unit' => $item->kode_unit,
                ]),
            ];
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Data berhasil ditemukan.',
                'data' => $data,
                'tgl_cetak' => $tanggalCetak,
            ]);
        }

        $menus = $this->getMenus();
        $title = 'Cetak Musyarakah';

        alert()->success('Berhasil!', 'Data berhasil ditemukan.');
        return view('admin.cetak_musyarakah.result', compact('results', 'data', 'menus', 'title'));
    }
}
