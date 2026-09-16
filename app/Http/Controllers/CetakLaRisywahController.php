<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CetakLaRisywahController extends BaseController
{
    public function index()
    {
        $menus = $this->getMenus();
        $title = 'Cetak La Risywah';

        return view("admin.cetak_larisywah.index", compact("menus", "title"));
    }

    public function hasil(Request $request)
    {
        $menus = $this->getMenus();
        $title = 'Cetak La Risywah';

        $request->validate([
            'kode_kelompok' => 'required',
            'tanggal' => 'required|date_format:Y-m-d',
            'unit' => 'required|string'
        ]);

        $tanggalCetak = \Carbon\Carbon::createFromFormat('Y-m-d', $request->tanggal)->format('Y-m-d');
        $kodeKelompok = $request->kode_kelompok;
        $unit = $request->unit;

        $results = DB::table('temp_akad_mus')
            ->where('tgl_akad', $tanggalCetak)
            ->where('code_kel', $kodeKelompok)
            ->where('unit', $unit)
            ->where('status_app', 'MUSYARAKAH')
            ->get();

        if ($results->isEmpty()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data yang ditemukan untuk tanggal atau kode kelompok tersebut.',
                ]);
            }

            alert()->error('Oops!', 'Tidak ada data yang ditemukan untuk tanggal atau kode kelompok tersebut.');
            return redirect()->back();
        }

        if ($request->ajax()) {
            $iframeUrl = route('pdf.generateLaRisywah', [
                'feature' => 'cetak_larisywah',
                'kelompok' => $kodeKelompok,
                'date' => $tanggalCetak,
                'unit' => $unit,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil ditemukan.',
                'iframe_url' => $iframeUrl,
            ]);
        }

        alert()->success('Berhasil!', 'Data berhasil ditemukan.');
        return view('admin.cetak_larisywah.result', compact('results', 'menus', 'title'));
    }
}
