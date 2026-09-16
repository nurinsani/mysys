<?php

namespace App\Http\Controllers;

use App\Http\Requests\Transaksi\StoreInputTransaksiRequest;
use App\Services\Transaksi\TransaksiService;
use App\Repositories\Contracts\AnggotaRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InputTransaksiController extends BaseController
{
    public function __construct(
        protected TransaksiService $transaksiService,
        protected AnggotaRepositoryInterface $anggotaRepository
    ) {
    }

    public function index()
    {
        $title = 'Input Transaksi';
        $menus = $this->getMenus();
        return view('admin.input_transaksi.index', compact('menus', 'title'));
    }

    public function getByCif($cif)
    {
        try {
            $cif = $this->anggotaRepository->findByCif($cif);

            if (!$cif) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nama dengan CIF tersebut tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'nama' => $cif->nama
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server'
            ], 500);
        }
    }

    public function store(StoreInputTransaksiRequest $request)
    {
        $result = $this->transaksiService->simpanTransaksi($request->validated());

        return response()->json($result['body'], $result['status']);
    }

    public function getHistory($cif)
    {
        try {
            $today = now()->format('Y-m-d');

            // Kolom disebutkan eksplisit (bukan `table.*`) karena `simpanan`
            // tidak punya kolom deleted_at/created_by/updated_by/ip_address
            // yang ada di simpanan_pokok/simpanan_wajib — UNION ALL butuh
            // jumlah kolom yang sama persis di tiap SELECT.
            $kolom = ['reff', 'buss_date', 'norek', 'unit', 'cif', 'code_kel', 'debet', 'type', 'kredit', 'userid', 'ket', 'cao', 'blok', 'tgl_input', 'kode_transaksi'];

            $wajib = DB::table('simpanan_wajib')
                ->where('cif', $cif)
                ->whereDate('tgl_input', $today)
                ->select(array_merge($kolom, [DB::raw("'Wajib' as jenis")]));

            $pokok = DB::table('simpanan_pokok')
                ->where('cif', $cif)
                ->whereDate('tgl_input', $today)
                ->select(array_merge($kolom, [DB::raw("'Pokok' as jenis")]));

            // Ambil transaksi dari simpanan (penarikan tunai, setor angsuran,
            // pemindahbukuan — jenis_transaksi 2/3/4 di TransaksiService
            // menulis ke sini, sebelumnya tidak pernah ikut ditampilkan).
            $simpanan = DB::table('simpanan')
                ->where('cif', $cif)
                ->whereDate('tgl_input', $today)
                ->select(array_merge($kolom, [DB::raw("'Simpanan' as jenis")]));

            $transaksiGabungan = $wajib->unionAll($pokok)->unionAll($simpanan)
                ->orderBy('tgl_input', 'asc')
                ->get();

            $nama = DB::table('anggota')
                ->where('cif', $cif)
                ->value('nama');

            $saldo = 0;
            foreach ($transaksiGabungan as $item) {
                $saldo += $item->kredit - $item->debet;
                $item->nama = $nama;
                $item->saldo = $saldo;
            }

            return response()->json([
                'success' => true,
                'data' => $transaksiGabungan,
                'message' => 'Data transaksi berhasil digabung dan diambil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat transaksi: ' . $e->getMessage()
            ], 500);
        }
    }
}
