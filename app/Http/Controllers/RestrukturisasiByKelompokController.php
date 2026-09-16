<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RestrukturisasiByKelompokController extends BaseController
{
    public function index()
    {
        $menus = $this->getMenus();
        $title = 'Restrukturisasi By Kelompok';

        return view("admin.restrukturisasi_by_kelompok.index", compact("menus", "title"));
    }

    public function suggestKelompok(Request $request)
    {
        $term = $request->input('term');
        $unit = $request->input('unit');
        $results = DB::table('kelompok')
            ->where('code_unit', $unit)
            ->where(function ($query) use ($term) {
                $query->where('code_kel', 'LIKE', "%{$term}%")
                    ->orWhere('nama_kel', 'LIKE', "%{$term}%");
            })
            ->select('code_kel', 'nama_kel', 'cao')
            ->limit(10)
            ->get();
        return response()->json($results);
    }

    public function searchKelompok(Request $request)
    {
        $request->validate([
            'kode_kelompok' => 'required',
            'unit' => 'required',
            'tenor' => 'required|integer|min:1',
        ]);
        $kode_kelompok = $request->kode_kelompok;
        $unit = $request->unit;
        $tenor = $request->tenor;
        $kelompok = DB::table('kelompok')
            ->where('code_kel', $kode_kelompok)
            ->where('code_unit', $unit)
            ->first();
        if (!$kelompok) {
            return response()->json(['results' => []]);
        }
        $pembiayaan = DB::table('pembiayaan')
            ->where('code_kel', $kode_kelompok)
            ->where('unit', $unit)
            ->get();
        $noa = $pembiayaan->count();
        $total_pembiayaan = $pembiayaan->sum('os');
        $results = [
            [
                'code_kel' => $kelompok->code_kel,
                'nama_kel' => $kelompok->nama_kel,
                'cao' => $kelompok->cao,
                'noa' => $noa,
                'total_pembiayaan' => $total_pembiayaan,
                'tenor' => $tenor,
            ]
        ];
        return response()->json(['results' => $results]);
    }

    public function restrukturisasi(Request $request)
    {
        $request->validate([
            'kode_kelompok' => 'required',
            'unit' => 'required',
            'jenis_rest' => 'required',
            'dari_simpanan' => 'required',
            'tenor' => 'required|integer',
            'param_tanggal' => 'required',
            'id_admin' => 'required',
        ]);

        $kode_kelompok = $request->kode_kelompok;
        $unit = $request->unit;
        $jenis_rest = $request->jenis_rest;
        $dari_simpanan = $request->dari_simpanan;
        $tenor = (int) $request->tenor;
        $param_tanggal = $request->param_tanggal;
        $id_admin = $request->id_admin;
        $pembiayaanList = DB::table('pembiayaan')
            ->where('code_kel', $kode_kelompok)
            ->where('unit', $unit)
            ->get();
        if ($pembiayaanList->isEmpty()) {
            return response()->json(['message' => 'Tidak ada anggota pada kelompok ini.'], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($pembiayaanList as $akad) {
                $os = (float) $akad->os;
                $saldo_margin = (float) $akad->saldo_margin;
                $cif = $akad->cif;
                $pokok = $os - $saldo_margin;
                $total_restrukturisasi = 0;
                $saldo_simpanan = 0;
                $pokok_sesudah_simpanan = null;
                $simpanan_dipakai = false;

                if ($jenis_rest === 'Pokok') {
                    $total_restrukturisasi = $pokok;
                    if ($dari_simpanan === 'Ya') {
                        $simpanan = DB::table('simpanan')
                            ->where('cif', $cif)
                            ->selectRaw('COALESCE(SUM(kredit),0) as total_kredit, COALESCE(SUM(debet),0) as total_debet')
                            ->first();
                        $saldo_simpanan = ($simpanan->total_kredit ?? 0) - ($simpanan->total_debet ?? 0);
                        if ($saldo_simpanan > 0) {
                            $total_restrukturisasi = max(0, $total_restrukturisasi - $saldo_simpanan);
                            $pokok_sesudah_simpanan = max(0, $pokok - $saldo_simpanan);
                            $simpanan_dipakai = true;
                        }
                    }
                } else { // Pokok+Margin
                    $pokok_asli = $pokok;
                    if ($dari_simpanan === 'Ya') {
                        $simpanan = DB::table('simpanan')
                            ->where('cif', $cif)
                            ->selectRaw('COALESCE(SUM(kredit),0) as total_kredit, COALESCE(SUM(debet),0) as total_debet')
                            ->first();
                        $saldo_simpanan = ($simpanan->total_kredit ?? 0) - ($simpanan->total_debet ?? 0);
                        if ($saldo_simpanan > 0) {
                            $pokok = max(0, $pokok - $saldo_simpanan);
                            $pokok_sesudah_simpanan = $pokok;
                            $simpanan_dipakai = true;
                        }
                    }
                    $total_restrukturisasi = $pokok + $saldo_margin;
                }

                $angsuran_baru = $tenor > 0 ? $total_restrukturisasi / $tenor : 0;

                $lastPayment = DB::table('pembiayaan_detail')
                    ->where('cif', $cif)
                    ->where('unit', $unit)
                    ->orderByDesc('tgl_jatuh_tempo')
                    ->first();
                $pembayaranKe = $lastPayment ? (int) $lastPayment->cicilan : 0;

                $startDate = $lastPayment ? \Carbon\Carbon::parse($lastPayment->tgl_jatuh_tempo)->addDays(7) : \Carbon\Carbon::now()->addDays(7);
                $tanggalLibur = DB::table('param_tgl')->pluck('param_tgl')->toArray();
                $adjustedTglJatuhTempo = [];
                for ($i = 0; $i < $tenor; $i++) {
                    $date = $startDate->copy()->addDays($i * 7);
                    $formattedDate = $date->format('Y-m-d');
                    while (in_array($formattedDate, $tanggalLibur)) {
                        $date->addDays(7);
                        $formattedDate = $date->format('Y-m-d');
                    }
                    $adjustedTglJatuhTempo[] = $date->format('Y-m-d H:i:s');
                }

                if ($simpanan_dipakai && $pokok_sesudah_simpanan !== null) {
                    DB::table('pembiayaan')
                        ->where('cif', $cif)
                        ->where('unit', $unit)
                        ->update(['os' => $pokok_sesudah_simpanan]);
                }

                // Insert transaksi for restrukturisasi (before pembiayaan_detail insertion)
                $keterangan_transaksi = (strtolower($jenis_rest) === 'pokok+margin' || strtolower($jenis_rest) === 'pokok margin')
                    ? 'Restrukturisasi Pokok Margin AN ' . $akad->nama
                    : 'Restrukturisasi Pokok AN ' . $akad->nama;
                $transaksiData = [];
                if (strtolower($jenis_rest) === 'pokok+margin' || strtolower($jenis_rest) === 'pokok margin') {
                    $transaksiData = [
                        [
                            'id_transaksi' => null,
                            'unit' => $unit,
                            'kode_transaksi' => 'BS-' . $unit . '-' . Str::random(7),
                            'kode_rekening' => '1411000',
                            'tanggal_transaksi' => date('Y-m-d H:i:s', strtotime($param_tanggal)),
                            'jenis_transaksi' => 'bukti SYSTEM',
                            'keterangan_transaksi' => $keterangan_transaksi,
                            'debet' => $pokok + $saldo_margin,
                            'kredit' => 0,
                            'tanggal_posting' => date('Y-m-d'),
                            'keterangan_posting' => '',
                            'id_admin' => $id_admin,
                            'ip_address' => request()->ip(),
                        ],
                        [
                            'id_transaksi' => null,
                            'unit' => $unit,
                            'kode_transaksi' => 'BS-' . $unit . '-' . Str::random(7),
                            'kode_rekening' => '1421000',
                            'tanggal_transaksi' => date('Y-m-d H:i:s', strtotime($param_tanggal)),
                            'jenis_transaksi' => 'bukti SYSTEM',
                            'keterangan_transaksi' => $keterangan_transaksi,
                            'debet' => 0,
                            'kredit' => $saldo_margin,
                            'tanggal_posting' => date('Y-m-d'),
                            'keterangan_posting' => '',
                            'id_admin' => $id_admin,
                            'ip_address' => request()->ip(),
                        ],
                        [
                            'id_transaksi' => null,
                            'unit' => $unit,
                            'kode_transaksi' => 'BS-' . $unit . '-' . Str::random(7),
                            'kode_rekening' => '1423000',
                            'tanggal_transaksi' => date('Y-m-d H:i:s', strtotime($param_tanggal)),
                            'jenis_transaksi' => 'bukti SYSTEM',
                            'keterangan_transaksi' => $keterangan_transaksi,
                            'debet' => $saldo_margin,
                            'kredit' => 0,
                            'tanggal_posting' => date('Y-m-d'),
                            'keterangan_posting' => '',
                            'id_admin' => $id_admin,
                            'ip_address' => request()->ip(),
                        ],
                        [
                            'id_transaksi' => null,
                            'unit' => $unit,
                            'kode_transaksi' => 'BS-' . $unit . '-' . Str::random(7),
                            'kode_rekening' => '1413000',
                            'tanggal_transaksi' => date('Y-m-d H:i:s', strtotime($param_tanggal)),
                            'jenis_transaksi' => 'bukti SYSTEM',
                            'keterangan_transaksi' => $keterangan_transaksi,
                            'debet' => 0,
                            'kredit' => $pokok + $saldo_margin,
                            'tanggal_posting' => date('Y-m-d'),
                            'keterangan_posting' => '',
                            'id_admin' => $id_admin,
                            'ip_address' => request()->ip(),
                        ]
                    ];
                } else {
                    $transaksiData = [
                        [
                            'id_transaksi' => null,
                            'unit' => $unit,
                            'kode_transaksi' => 'BS-' . $unit . '-' . Str::random(7),
                            'kode_rekening' => '1411000',
                            'tanggal_transaksi' => date('Y-m-d H:i:s', strtotime($param_tanggal)),
                            'jenis_transaksi' => 'bukti SYSTEM',
                            'keterangan_transaksi' => $keterangan_transaksi,
                            'debet' => $pokok,
                            'kredit' => 0,
                            'tanggal_posting' => date('Y-m-d'),
                            'keterangan_posting' => '',
                            'id_admin' => $id_admin,
                            'ip_address' => request()->ip(),
                        ],
                        [
                            'id_transaksi' => null,
                            'unit' => $unit,
                            'kode_transaksi' => 'BS-' . $unit . '-' . Str::random(7),
                            'kode_rekening' => '1421000',
                            'tanggal_transaksi' => date('Y-m-d H:i:s', strtotime($param_tanggal)),
                            'jenis_transaksi' => 'bukti SYSTEM',
                            'keterangan_transaksi' => $keterangan_transaksi,
                            'debet' => 0,
                            'kredit' => 0,
                            'tanggal_posting' => date('Y-m-d'),
                            'keterangan_posting' => '',
                            'id_admin' => $id_admin,
                            'ip_address' => request()->ip(),
                        ],
                        [
                            'id_transaksi' => null,
                            'unit' => $unit,
                            'kode_transaksi' => 'BS-' . $unit . '-' . Str::random(7),
                            'kode_rekening' => '1423000',
                            'tanggal_transaksi' => date('Y-m-d H:i:s', strtotime($param_tanggal)),
                            'jenis_transaksi' => 'bukti SYSTEM',
                            'keterangan_transaksi' => $keterangan_transaksi,
                            'debet' => $saldo_margin,
                            'kredit' => 0,
                            'tanggal_posting' => date('Y-m-d'),
                            'keterangan_posting' => '',
                            'id_admin' => $id_admin,
                            'ip_address' => request()->ip(),
                        ],
                        [
                            'id_transaksi' => null,
                            'unit' => $unit,
                            'kode_transaksi' => 'BS-' . $unit . '-' . Str::random(7),
                            'kode_rekening' => '1413000',
                            'tanggal_transaksi' => date('Y-m-d H:i:s', strtotime($param_tanggal)),
                            'jenis_transaksi' => 'bukti SYSTEM',
                            'keterangan_transaksi' => $keterangan_transaksi,
                            'debet' => 0,
                            'kredit' => $pokok + $saldo_margin,
                            'tanggal_posting' => date('Y-m-d'),
                            'keterangan_posting' => '',
                            'id_admin' => $id_admin,
                            'ip_address' => request()->ip(),
                        ]
                    ];
                }
                DB::table('tabel_transaksi')->insert($transaksiData);

                for ($i = 0; $i < $tenor; $i++) {
                    $cicilan = $pembayaranKe + 1 + $i;
                    $jumlah_bayar = $angsuran_baru;
                    if ($i == $tenor - 1) {
                        // Last installment, adjust for rounding
                        if ($jenis_rest === 'Pokok') {
                            $total = $os - $saldo_margin;
                            if ($dari_simpanan === 'Ya' && $saldo_simpanan > 0) {
                                $total = max(0, $total - $saldo_simpanan);
                            }
                        } else {
                            $total = $pokok_asli + $saldo_margin;
                            if ($dari_simpanan === 'Ya' && $saldo_simpanan > 0) {
                                $total = max(0, $total - $saldo_simpanan);
                                $total += $saldo_margin;
                            }
                        }
                        $jumlah_bayar = $total - ($angsuran_baru * ($tenor - 1));
                    }
                    DB::table('pembiayaan_detail')->insert([
                        'id' => null,
                        'id_pinjam' => $akad->no_anggota,
                        'cicilan' => $cicilan,
                        'angsuran_pokok' => $akad->pokok,
                        'margin' => $akad->ijaroh,
                        'tgl_jatuh_tempo' => $adjustedTglJatuhTempo[$i],
                        'tgl_bayar' => null,
                        'jumlah_bayar' => $jumlah_bayar,
                        'keterangan' => 'Restrukturisasi by kelompok',
                        'cif' => $akad->cif,
                        'unit' => $unit,
                        'ao' => $akad->cao,
                        'code_kel' => $akad->code_kel,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                DB::table('tunggakan')->where('cif', $cif)->delete();
                DB::table('history_rest')->insert([
                    'tgl_rest' => now()->format('Y-m-d'),
                    'code_kel' => $akad->code_kel,
                    'cif' => $akad->cif,
                    'plafond' => $akad->plafond,
                    'pokok' => $akad->pokok,
                    'margin' => $akad->saldo_margin,
                    'angsuran' => $akad->angsuran,
                    'tenor' => $tenor,
                    'jenis_rest' => $jenis_rest,
                    'status' => 'REST JATPO KELOMPOK',
                    'angsuran_baru' => $angsuran_baru,
                    'tenor_baru' => $tenor,
                    'jatpo_baru' => isset($adjustedTglJatuhTempo[$tenor - 1]) ? \Carbon\Carbon::parse($adjustedTglJatuhTempo[$tenor - 1])->format('Y-m-d H:i:s') : null,
                    'tgl_jatpo' => $akad->maturity_date ? \Carbon\Carbon::parse($akad->maturity_date)->format('Y-m-d H:i:s') : null,
                    'tgl_akad_baru' => $param_tanggal ? \Carbon\Carbon::parse($param_tanggal)->format('Y-m-d H:i:s') : null,
                ]);
            }
            DB::commit();
            return response()->json(['message' => 'Restrukturisasi berhasil dilakukan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
}
