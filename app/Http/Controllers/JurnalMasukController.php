<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JurnalMasukController extends BaseController
{
    public function index()
    {
        $title = 'Jurnal Masuk';
        $menus = $this->getMenus();

        $paramTanggal = Auth::user()->param_tanggal;

        $kodeUnit = Auth::user()->unit;
        $random = strtoupper(Str::random(7));
        $kodeTransaksi = 'KM/' . $kodeUnit . $random;

        return view('admin.jurnal_masuk.index', compact('menus', 'title', 'kodeTransaksi', 'paramTanggal'));
    }

    public function getCoa(Request $request)
    {
        $cari = $request->input('cari');

        $results = DB::table('coa')
            ->select('kode_rek', 'nama_rek')
            ->where('kode_rek', 'like', '%'.$cari.'%')
            ->orWhere('nama_rek', 'like', '%'.$cari.'%')
            ->limit(10)
            ->get();

        return response()->json($results);
    }

    public function simpan(Request $request)
    {
        $data = $request->input('transaksi');

        $kodeUnit = Auth::user()->unit;

        $kodeGL = DB::table('branch')
            ->where('kode_branch', $kodeUnit)
            ->value('GL');

        try {
            DB::beginTransaction();

            foreach ($data as $item) {
                DB::table('tabel_transaksi')->insert([
                    'unit' => Auth::user()->unit,
                    'kode_transaksi' => $item['kode_transaksi'],
                    'kode_rekening' => $item['kode_rekening'],
                    'tanggal_transaksi' => $item['tanggal_transaksi'],
                    'jenis_transaksi' => $item['jenis_transaksi'],
                    'keterangan_transaksi' => $item['keterangan_transaksi'],
                    'debet' => 0,
                    'kredit' => $item['kredit'],
                    'tanggal_posting' => $item['tanggal_transaksi'],
                    'keterangan_posting' => 'Post',
                    'id_admin' => Auth::user()->id,
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                DB::table('tabel_transaksi')->insert([
                    'unit' => Auth::user()->unit,
                    'kode_transaksi' => $item['kode_transaksi'],
                    'kode_rekening' => $kodeGL,
                    'tanggal_transaksi' => $item['tanggal_transaksi'],
                    'jenis_transaksi' => $item['jenis_transaksi'],
                    'keterangan_transaksi' => $item['keterangan_transaksi'],
                    'debet' => $item['kredit'],
                    'kredit' => 0,
                    'tanggal_posting' => $item['tanggal_transaksi'],
                    'keterangan_posting' => 'Post',
                    'id_admin' => Auth::user()->id,
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Data berhasil disimpan.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menyimpan data!',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
