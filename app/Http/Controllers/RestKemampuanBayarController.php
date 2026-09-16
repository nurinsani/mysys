<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\temp_akad_mus;
use App\Models\branch;
use App\Models\Pembiayaan;
use App\Repositories\Contracts\KelompokRepositoryInterface;
use Carbon\Carbon;

class RestKemampuanBayarController extends BaseController
{
    public function __construct(protected KelompokRepositoryInterface $kelompokRepository)
    {
    }

    public function index()
    {
        $menus = $this->getMenus();
        $pembiayaan = DB::table('pembiayaan')
        ->selectRaw('SUM(os - saldo_margin) as os, COUNT(cif) as noa')
        ->first();
        $title = 'Setoran Lima Persen';

        return view('admin.rest_kemampuan_bayar.index',compact('menus','pembiayaan','title'));

    }

    public function getData(Request $request)
    {
        $query = Pembiayaan::query()
        ->join('kelompok', 'pembiayaan.code_kel', '=', 'kelompok.code_kel')
        ->where('unit', Auth()->user()->unit)
        ->select(
            'pembiayaan.*',
            'kelompok.nama_kel',
        );



    if ($request->kode_kelompok) {
        $query->where('kelompok.code_kel', 'LIKE', '%' . $request->kode_kelompok . '%');
    }


    $data = $query->get();

    return response()->json($data);

    }

    public function realisasiRestKemampuanBayar(Request $request)
    {


            $cekbox =$request->ids;
            $kemampuanBayar = $request->input('kemampuan_bayar', []);



        if (empty($cekbox) || !is_array($cekbox)) {
            return response()->json(['message' => 'Tidak ada data yang dipilih.'], 400);
        }
        if (!$kemampuanBayar || !is_array($kemampuanBayar)) {
            return response()->json(['message' => 'Kemampuan bayar tidak ditemukan.'], 400);
        }

        $userid = auth()->user()->id;
        $tgl_system = date('Y-m-d H:i:s');
        $unit = auth()->user()->unit;



        if (!$cekbox) {
            return response()->json([
                'success' => true,
                'message' => 'Anda belum memilih data'
            ])->setStatusCode(400);

        }

        DB::beginTransaction();
        try {
            foreach ($cekbox as $value) {
                $nilai = isset($kemampuanBayar[$value]) ? (int) $kemampuanBayar[$value] : 0;

        if ($nilai < 5000) {
            return response()->json([
                 'success' => true,
                               'message' => 'Nominal kemampuan bayar Kerang dari 5.000 Rupiah untuk CIF: ' . $value,
            ], 400);


            }

                $loan = Pembiayaan::where('cif', $value)->first();

                if (!$loan) continue;

                $kode_trans = 'BU/' . $loan->unit . strtoupper(\Str::random(8));
                $nama = $loan->nama;
                $unit = $loan->unit;
                $code_kel = $loan->code_kel;
                $no_anggota = $loan->no_anggota;
                $saldo_margin = $loan->saldo_margin;
                $norek = $no_anggota;
                $cif = $loan->cif;
                $cao = $loan->cao;
                $os = $loan->os;
                $plafond = $loan->plafond;
                $nominal = $plafond * 5 / 100;
                $tenor_baru=$os/$kemampuanBayar[$value];
			    $ijaroh_baru=$saldo_margin/$tenor_baru;
			    $pokok_baru = $kemampuanBayar[$value] - $ijaroh_baru;
			    $angsuran_baru =$kemampuanBayar[$value];

                $tanggal = auth()->user()->param_tanggal ;
                $tgl_m = Carbon::parse($tanggal);
                $maturity_date_res = $tgl_m->copy()->addWeeks($tenor_baru)->format('Y-m-d');


                $loan->angsuran = $angsuran_baru;
                $loan->tenor = $tenor_baru;
                $loan->pokok = $pokok_baru;
                $loan->ijaroh = $ijaroh_baru;
                $loan->maturity_date = $maturity_date_res;
                $loan->run_tenor = 0;
                $loan->tgl_akad = $tgl_system;
                $loan->bulat = $angsuran_baru;
                $loan->ke = 1;
                $loan->save();
                $deleted = DB::table('pembiayaan_detail')->where('cif', $value)->delete();
                $deleted = DB::table('tunggakan')->where('cif', $value)->delete();

                $history_rest=[
                    'tgl_rest' => $tgl_system,
                    'code_kel' => $loan->code_kel,
                    'cif' => $loan->cif,
                    'plafond' => $loan->plafond,
                    'pokok' => $loan->pokok,
                    'margin' => $loan->saldo_margin,
                    'angsuran' => $loan->angsuran,
                    'tenor' => $loan->tenor,
                    'jenis_rest' => 'KEMAMPUAN BAYAR',
                    'status' => $loan->status_app,
                    'angsuran_baru' => $angsuran_baru,
                    'jatpo_baru' => $maturity_date_res,
                    'tgl_jatpo' => $loan->maturity_date,
                    'tenor_baru' => $tenor_baru,
                    'tgl_akad_baru' => $tgl_system

                ];

                DB::table('history_rest')->insert($history_rest);

            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rest Kemampuan Bayar Anggota Berhasil Dilakukan',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getSetKelompok(Request $request)
    {
        $kelompok = $this->kelompokRepository->search($request->q, auth()->user()->unit, 20);

        return response()->json($kelompok);
    }
}
