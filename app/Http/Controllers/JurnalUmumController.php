<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;


class JurnalUmumController extends BaseController
{
    public function index()
    {
        $title = 'Jurnal Masuk';
        $menus = $this->getMenus();

        $kodeUnit = Auth::user()->unit;
        $random = strtoupper(Str::random(7));
        $kodeTransaksi = 'BU/' . $kodeUnit . $random;

        return view('admin.jurnal_umum.index', compact('menus', 'title', 'kodeTransaksi'));
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

        try {
            DB::beginTransaction();

            foreach ($data as $item) {
                $debet = $item['posisi'] === 'debet' ? $item['jumlah'] : 0;
                $kredit = $item['posisi'] === 'kredit' ? $item['jumlah'] : 0;

                DB::table('tabel_transaksi')->insert([
                    'unit' => Auth::user()->unit,
                    'kode_transaksi' => $item['kode_transaksi'],
                    'kode_rekening' => $item['kode_rekening'],
                    'tanggal_transaksi' => $item['tanggal_transaksi'],
                    'jenis_transaksi' => 'Jurnal UMUM',
                    'keterangan_transaksi' => $item['keterangan_transaksi'],
                    'debet' => $debet,
                    'kredit' => $kredit,
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

    public function cetak(Request $request)
    {
        $transaksi = $request->input('transaksi', []);

        $debet = collect($transaksi)->where('posisi', 'debet')->values()->all();
        $kredit = collect($transaksi)->where('posisi', 'kredit')->values()->all();

        $pdf = Pdf::loadView('admin.jurnal_umum.cetak_pdf', [
            'transaksi' => $transaksi,
            'debet' => $debet,
            'kredit' => $kredit
        ])->setPaper('A4', 'portrait');

        return $pdf->stream('bukti-jurnal.pdf');
    }

}
