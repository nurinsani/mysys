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
        $menus = $this->getMenus();
        $title = 'Cetak Musyarakah';

        $request->validate([
            'tanggal_cetak' => 'required|date_format:Y-m-d',
            'unit' => 'required|string'
        ]);

        $tanggalCetak = \Carbon\Carbon::createFromFormat('Y-m-d', $request->tanggal_cetak)->format('Y-m-d');
        $unit = $request->unit;

        $results = DB::table('temp_akad_mus')
            ->where('tgl_akad', $tanggalCetak)
            ->where('unit', $unit)
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

        if ($request->ajax()) {
            $iframeUrl = route('pdf.generateMusyarakah', [
                'feature' => 'cetak_musyarakah',
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
        return view('admin.cetak_musyarakah.result', compact('results', 'menus', 'title'));
    }
}
