<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JurnalKeluarController extends BaseController
{
    public function index()
    {
        $title = 'Jurnal Keluar';
        $menus = $this->getMenus();

        $paramTanggal = Auth::user()->param_tanggal;

        $kodeUnit = Auth::user()->unit;
        $random = strtoupper(Str::random(7));
        $kodeTransaksi = 'KK/' . $kodeUnit . $random;

        $kodeGL = DB::table('branch')
            ->where('kode_branch', $kodeUnit)
            ->value('GL');

        return view('admin.jurnal_keluar.index', compact(
            'menus',
            'title',
            'kodeTransaksi',
            'kodeGL',
            'paramTanggal'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->input('transaksi');

        $kodeUnit = Auth::user()->unit;

        $kodeGL = DB::table('branch')
            ->where('kode_branch', $kodeUnit)
            ->value('GL');

        DB::beginTransaction();
        try {
            foreach ($data as $item) {
                DB::table('tabel_transaksi')->insert([
                    'unit' => Auth::user()->unit,
                    'kode_transaksi' => $item['kode_transaksi'],
                    'kode_rekening' => $item['kode_rekening'],
                    'tanggal_transaksi' => $item['tanggal_transaksi'],
                    'jenis_transaksi' => 'Jurnal UMUM',
                    'keterangan_transaksi' => $item['keterangan_transaksi'],
                    'debet' => $item['jenis'] === 'debet' ? $item['jumlah'] : 0,
                    'kredit' => $item['jenis'] === 'kredit' ? $item['jumlah'] : 0,
                    'tanggal_posting' => $item['tanggal_transaksi'],
                    'keterangan_posting' => 'Post',
                    'id_admin' => Auth::user()->id,
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                if ($item['jenis'] === 'debet' && $item['jenis_transaksi'] === 'lainnya') {
                    DB::table('tabel_transaksi')->insert([
                        'unit' => Auth::user()->unit,
                        'kode_transaksi' => $item['kode_transaksi'],
                        'kode_rekening' => $kodeGL,
                        'tanggal_transaksi' => $item['tanggal_transaksi'],
                        'jenis_transaksi' => 'Jurnal Keluar',
                        'keterangan_transaksi' => $item['keterangan_transaksi'],
                        'debet' => 0,
                        'kredit' => $item['jumlah'],
                        'tanggal_posting' => $item['tanggal_transaksi'],
                        'keterangan_posting' => 'Post',
                        'id_admin' => Auth::user()->id,
                        'ip_address' => request()->ip(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            DB::commit();
            return response()->json(['message' => 'Data berhasil disimpan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
