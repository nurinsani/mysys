<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PelunasanController extends BaseController
{
    public function index ()
    {
        $title = 'Pelunasan';
        $menus = $this->getMenus();
        return view('admin.pelunasan.index', compact('title', 'menus'));
    }

    public function cari(Request $request)
    {
        $cari = $request->input('cari');

        $results = DB::table('anggota')
            ->select('nama', 'cif')
            ->where('cif', 'like', '%'.$cari.'%')
            ->orWhere('cif', 'like', '%'.$cari.'%')
            ->limit(10)
            ->get();

        return response()->json($results);
    }

    public function getAnggota(Request $request)
    {
        $cif = $request->cif;

        $anggota = DB::table('anggota')
            ->join('pembiayaan', 'anggota.no', '=', 'pembiayaan.no_anggota')
            ->where('anggota.cif', $cif)
            ->select(
                'anggota.*',
                'pembiayaan.plafond',
                'pembiayaan.angsuran',
                'pembiayaan.maturity_date as tgl_jatuh_tempo',
                'pembiayaan.os',
                DB::raw('(SELECT SUM(kredit - debet) FROM simpanan WHERE norek = anggota.norek) as saldo_rekening')
            )
            ->get();
        return response()->json($anggota);
    }

    public function proses(Request $request)
    {
        try {
            $norek = $request->norek;

            $simpanan = DB::table('simpanan')
                ->where('norek', $norek)
                ->selectRaw('COALESCE(SUM(kredit), 0) - COALESCE(SUM(debet), 0) as saldo')
                ->value('saldo');

            $anggota = DB::table('anggota')
                ->where('norek', $norek)
                ->select('no', 'unit', 'nama', 'norek', 'cif', 'kode_kel', 'cao')
                ->first();

            if (!$anggota) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data anggota tidak ditemukan'
                ], 404);
            }

            $pembiayaan = DB::table('pembiayaan')
                ->where('no_anggota', $anggota->no)
                ->first();

            if (!$pembiayaan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data pembiayaan tidak ditemukan'
                ], 404);
            }

            if ($simpanan < $pembiayaan->os) {
                return response()->json([
                    'success' => false,
                    'message' => 'Saldo simpanan tidak mencukupi untuk pelunasan'
                ], 400);
            }

            $unit = $anggota->unit;
            $kodeTransaksi = 'BU/' . $unit . strtoupper(Str::random(8));
            $tgl_system = now()->format('Y-m-d H:i:s');
            $user_id = Auth::user()->id;
            $ket = 'Pelunasan an ' . $anggota->nama;
            $reff = generate_reff($unit);

            $transaksi = [
                [
                    'unit' => $unit,
                    'kode_transaksi' => $kodeTransaksi,
                    'kode_rekening' => '2101000',
                    'tanggal_transaksi' => $tgl_system,
                    'jenis_transaksi' => 'Bukti SYSTEM',
                    'keterangan_transaksi' => $ket,
                    'debet' => $simpanan,
                    'kredit' => '0',
                    'tanggal_posting' => $tgl_system,
                    'keterangan_posting' => 'Post',
                    'id_admin' => $user_id,
                    'ip_address' => request()->ip(),
                ],
                [
                    'unit' => $unit,
                    'kode_transaksi' => $kodeTransaksi,
                    'kode_rekening' => '1413000',
                    'tanggal_transaksi' => $tgl_system,
                    'jenis_transaksi' => 'Bukti SYSTEM',
                    'keterangan_transaksi' => $ket,
                    'debet' => 0,
                    'kredit' => $pembiayaan->os + $pembiayaan->bagi_hasil,
                    'tanggal_posting' => $tgl_system,
                    'keterangan_posting' => 'Post',
                    'id_admin' => $user_id,
                    'ip_address' => request()->ip(),
                ],
                [
                    'unit' => $unit,
                    'kode_transaksi' => $kodeTransaksi,
                    'kode_rekening' => '1423000',
                    'tanggal_transaksi' => $tgl_system,
                    'jenis_transaksi' => 'Bukti SYSTEM',
                    'keterangan_transaksi' => $ket,
                    'debet' => $pembiayaan->bagi_hasil,
                    'kredit' => 0,
                    'tanggal_posting' => $tgl_system,
                    'keterangan_posting' => 'Post',
                    'id_admin' => $user_id,
                    'ip_address' => request()->ip(),
                ],
                [
                    'unit' => $unit,
                    'kode_transaksi' => $kodeTransaksi,
                    'kode_rekening' => '41002',
                    'tanggal_transaksi' => $tgl_system,
                    'jenis_transaksi' => 'Bukti SYSTEM',
                    'keterangan_transaksi' => $ket,
                    'debet' => 0,
                    'kredit' => $pembiayaan->bagi_hasil,
                    'tanggal_posting' => $tgl_system,
                    'keterangan_posting' => 'Post',
                    'id_admin' => $user_id,
                    'ip_address' => request()->ip(),
                ]
            ];

            DB::table('tabel_transaksi')->insert($transaksi);

            DB::table('simpanan')->insert([
                'buss_date' => now(),
                'norek' => $anggota->norek,
                'unit' => $anggota->unit,
                'cif' => $anggota->cif,
                'code_kel' => $anggota->kode_kel,
                'debet' => $simpanan,
                'type' => '04',
                'kredit' => 0,
                'userid' => $user_id,
                'ket' => $ket,
                'reff' => $reff,
                'cao' => $anggota->cao,
                'blok' => '2',
                'kode_transaksi' => $kodeTransaksi,
                'created_at' => now(),
                'updated_at' => now()
            ]);


            DB::table('pembiayaan')
                ->where('no_anggota', $anggota->no)
                ->update([
                    'os' => 0,
                    'saldo_margin' => 0,
                    'run_tenor' => 0,
                    'ke' => 0,
                    'last_payment' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Pelunasan berhasil diproses'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


}
