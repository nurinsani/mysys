<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestrukturisasiJatuhTempoController extends BaseController
{
    public function index()
    {
        $menus = $this->getMenus();
        $title = 'Restrukturisasi Jatuh Tempo';

        return view("admin.restrukturisasi_jatuh_tempo.index", compact("menus", "title"));
    }

    public function searchKelompok(Request $request)
    {
        $request->validate([
            'kode_kelompok' => 'required',
            'unit' => 'required',
        ]);

        $results = DB::table('pembiayaan')
            ->where('code_kel', $request->kode_kelompok)
            ->where('unit', $request->unit)
            ->get();

        return response()->json(['results' => $results]);
    }

    public function restrukturisasi(Request $request)
    {
        $request->validate([
            'cifs' => 'required|array',
            'unit' => 'required',
            'kode_kelompok' => 'required',
            'param_tanggal' => 'required',
        ]);

        foreach ($request->cifs as $cif) {
            $akad = DB::table('pembiayaan')->where('cif', $cif)->where('unit', $request->unit)->first();
            if (!$akad)
                continue;

            $now = now();
            if ($now->lt(\Carbon\Carbon::parse($akad->maturity_date))) {
                return response()->json(['error' => 'Restrukturisasi hanya dapat dilakukan setelah maturity date.'], 400);
            }

            $tunggakanRecords = DB::table('tunggakan')
                ->where('cif', $cif)
                ->where('unit', $request->unit)
                ->get();
            $totalKredit = $tunggakanRecords->sum('kredit');
            $totalDebet = $tunggakanRecords->sum('debet');
            $totalToBePaid = $totalKredit - $totalDebet;

            $angsuran = (int) $akad->angsuran;
            $newTenor = intdiv($totalToBePaid, $angsuran);
            if ($totalToBePaid % $angsuran !== 0) {
                $newTenor += 1;
            }
            if ($newTenor <= 0) {
                $newTenor = 1; // gaboleh minus ato 0 (karna di pakai sebagai denominator)
            }

            DB::table('tunggakan')->where('cif', $cif)->where('unit', $request->unit)->delete();

            $jumlahLunas = $totalToBePaid;

            $lastPayment = DB::table('pembiayaan_detail')
                ->where('cif', $cif)
                ->where('unit', $request->unit)
                ->orderByDesc('tgl_jatuh_tempo')
                ->first();
            $pembayaranKe = $lastPayment ? (int) $lastPayment->cicilan : 0;
            $startDate = $lastPayment ? \Carbon\Carbon::parse($lastPayment->tgl_jatuh_tempo)->addDays(7) : \Carbon\Carbon::now()->addDays(7);

            // sisanya sama kyk yang di realisasi murabahah
            $tanggalLibur = DB::table('param_tgl')->pluck('param_tgl')->toArray();
            $adjustedTglJatuhTempo = [];
            for ($i = 0; $i < $newTenor; $i++) {
                $date = $startDate->copy()->addDays($i * 7);
                $formattedDate = $date->format('Y-m-d');
                while (in_array($formattedDate, $tanggalLibur)) {
                    $date->addDays(7);
                    $formattedDate = $date->format('Y-m-d');
                }
                $adjustedTglJatuhTempo[] = $date->format('Y-m-d H:i:s');
            }
            for ($i = 0; $i < $newTenor; $i++) {
                $cicilan = $pembayaranKe + 1 + $i;
                if ($i < $newTenor - 1) {
                    $jumlah_bayar = $angsuran;
                } else {
                    $jumlah_bayar = $jumlahLunas - ($angsuran * ($newTenor - 1));
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
                    'keterangan' => 'Restrukturisasi jatuh tempo',
                    'cif' => $akad->cif,
                    'unit' => $request->unit,
                    'ao' => $akad->cao,
                    'code_kel' => $akad->code_kel,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // record buat auditting
            DB::table('history_rest')->insert([
                'tgl_rest' => now()->format('Y-m-d'),
                'code_kel' => $akad->code_kel,
                'cif' => $akad->cif,
                'plafond' => $akad->plafond,
                'pokok' => $akad->pokok,
                'margin' => $akad->saldo_margin,
                'angsuran' => $akad->angsuran,
                'tenor' => $newTenor,
                'jenis_rest' => 'jatuh tempo',
                'status' => 'REST JATPO',
                'angsuran_baru' => $angsuran,
                'tenor_baru' => $newTenor,
                'jatpo_baru' => isset($adjustedTglJatuhTempo[$newTenor - 1]) ? \Carbon\Carbon::parse($adjustedTglJatuhTempo[$newTenor - 1])->format('Y-m-d H:i:s') : null,
                'tgl_jatpo' => $akad->maturity_date ? \Carbon\Carbon::parse($akad->maturity_date)->format('Y-m-d H:i:s') : null,
                'tgl_akad_baru' => $request->param_tanggal ? \Carbon\Carbon::parse($request->param_tanggal)->format('Y-m-d H:i:s') : null,
            ]);
        }
        return response()->json(['message' => 'Restrukturisasi berhasil dilakukan.']);
    }
}
