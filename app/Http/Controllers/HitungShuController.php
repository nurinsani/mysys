<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HitungShuController extends BaseController
{
    public function index()
    {
        $title = 'Hitung SHU';
        $menus = $this->getMenus();
        $unit = Auth::user()->unit;

        $adaBelumPosting = DB::table('tabel_transaksi')
            ->where('unit', $unit)
            ->where('keterangan_posting', '')
            ->exists();

        return view('admin.hitung_shu.index', compact('title', 'menus', 'adaBelumPosting'));
    }

    public function proses()
    {
        $this->jalankanHitungShu(Auth::user()->unit);

        return redirect()->route('hitung-shu.index')->with('success', 'SHU berhasil dihitung');
    }

    protected function jalankanHitungShu(string $unit): void
    {
        DB::transaction(function () use ($unit) {
            $this->resetSaldo($unit);
            $this->hitungSaldoAkhir($unit);
            $this->rollupGroup($unit, 'gr_head');
            $this->rollupGroup($unit, 'gr_sub');
            $this->pisahRugiLabaNeraca($unit);
            $this->hitungSaldoJalan($unit);
            $this->hitungShu($unit);
            $this->refreshTabelRugiLaba($unit);
        });
    }

    private function resetSaldo(string $unit): void
    {
        DB::table('tabel_master')
            ->where('LINE_BALANCE', 'HEADING')
            ->where('unit', $unit)
            ->where('kode_rekening', '!=', '2500000')
            ->update(['saldo_awal' => 0, 'saldo_akhir' => 0, 'mut_debet' => 0, 'mut_kredit' => 0, 'sisa_debet' => 0, 'sisa_kredit' => 0]);

        DB::table('tabel_master')
            ->where('LINE_BALANCE', 'DETAIL')
            ->where('unit', $unit)
            ->update(['saldo_akhir' => 0]);
    }

    private function hitungSaldoAkhir(string $unit): void
    {
        DB::table('tabel_master')->where('unit', $unit)->where('gr_neraca', 'AKTIVA')
            ->update(['saldo_akhir' => DB::raw('saldo_awal + mut_debet - mut_kredit')]);

        DB::table('tabel_master')->where('unit', $unit)->where('gr_neraca', 'admin')->where('normal', 'debet')
            ->update(['saldo_akhir' => DB::raw('saldo_awal + mut_debet - mut_kredit')]);

        DB::table('tabel_master')->where('unit', $unit)->where('gr_neraca', 'admin')->where('normal', 'kredit')
            ->update(['saldo_akhir' => DB::raw('saldo_awal + mut_kredit - mut_debet')]);

        DB::table('tabel_master')->where('unit', $unit)->where('gr_neraca', 'PASIVA')
            ->update(['saldo_akhir' => DB::raw('saldo_awal + mut_kredit - mut_debet')]);

        DB::table('tabel_master')->where('unit', $unit)->whereBetween('kode_rekening', ['40000', '42399'])
            ->update(['saldo_akhir' => DB::raw('sisa_kredit - sisa_debet')]);

        DB::table('tabel_master')->where('unit', $unit)->whereBetween('kode_rekening', ['50000', '56999'])
            ->update(['saldo_akhir' => DB::raw('sisa_debet - sisa_kredit')]);

        DB::table('tabel_master')->where('unit', $unit)->whereBetween('kode_rekening', ['57000', '57501'])
            ->update(['saldo_akhir' => DB::raw('sisa_kredit - sisa_debet')]);

        DB::table('tabel_master')->where('unit', $unit)->whereBetween('kode_rekening', ['58000', '59999'])
            ->update(['saldo_akhir' => DB::raw('sisa_debet - sisa_kredit')]);
    }

    private function rollupGroup(string $unit, string $kolomGrup): void
    {
        $rows = DB::table('tabel_master')
            ->where('unit', $unit)
            ->where($kolomGrup, '>', 0)
            ->select(
                "{$kolomGrup} as kode_rekening",
                DB::raw('SUM(mut_debet) as mut_debet'),
                DB::raw('SUM(mut_kredit) as mut_kredit'),
                DB::raw('SUM(saldo_awal) as saldo_awal'),
                DB::raw('SUM(saldo_akhir) as saldo_akhir')
            )
            ->groupBy($kolomGrup)
            ->get();

        foreach ($rows as $row) {
            DB::table('tabel_master')
                ->where('unit', $unit)
                ->where('kode_rekening', $row->kode_rekening)
                ->update([
                    'saldo_awal' => $row->saldo_awal,
                    'saldo_akhir' => $row->saldo_akhir,
                    'mut_debet' => $row->mut_debet,
                    'mut_kredit' => $row->mut_kredit,
                ]);
        }
    }

    private function pisahRugiLabaNeraca(string $unit): void
    {
        DB::table('tabel_master')->where('unit', $unit)->where('posisi', 'rugi-laba')
            ->update(['rl_debet' => DB::raw('sisa_debet'), 'rl_kredit' => DB::raw('sisa_kredit')]);

        DB::table('tabel_master')->where('unit', $unit)->where('posisi', '<>', 'rugi-laba')
            ->update(['nrc_debet' => DB::raw('sisa_debet'), 'nrc_kredit' => DB::raw('sisa_kredit')]);
    }

    private function hitungSaldoJalan(string $unit): void
    {
        DB::table('tabel_master')->where('unit', $unit)->whereBetween('kode_rekening', ['40000', '42399'])
            ->update(['saldo_jalan' => DB::raw('mut_kredit - mut_debet')]);

        DB::table('tabel_master')->where('unit', $unit)->whereBetween('kode_rekening', ['50000', '56999'])
            ->update(['saldo_jalan' => DB::raw('mut_debet - mut_kredit')]);

        DB::table('tabel_master')->where('unit', $unit)->whereBetween('kode_rekening', ['57000', '57501'])
            ->update(['saldo_jalan' => DB::raw('mut_kredit - mut_debet')]);

        DB::table('tabel_master')->where('unit', $unit)->whereBetween('kode_rekening', ['58000', '58999'])
            ->update(['saldo_jalan' => DB::raw('mut_debet - mut_kredit')]);
    }

    private function hitungShu(string $unit): void
    {
        $pendapatan = DB::table('tabel_master')->where('unit', $unit)->where('kode_rekening', '40000')->first();
        $biaya = DB::table('tabel_master')->where('unit', $unit)->where('kode_rekening', '50000')->first();
        $pendapatanNonOps = DB::table('tabel_master')->where('unit', $unit)->where('kode_rekening', '57000')->first();
        $beban = DB::table('tabel_master')->where('unit', $unit)->where('kode_rekening', '58000')->first();

        $shuOpsAwal = ($pendapatan->saldo_awal ?? 0) - ($biaya->saldo_awal ?? 0);
        $shuOpsJalan = ($pendapatan->saldo_jalan ?? 0) - ($biaya->saldo_jalan ?? 0);
        $shuOpsAkhir = ($pendapatan->saldo_akhir ?? 0) - ($biaya->saldo_akhir ?? 0);

        $shuNonOpsAwal = ($pendapatanNonOps->saldo_awal ?? 0) - ($beban->saldo_awal ?? 0);
        $shuNonOpsJalan = ($pendapatanNonOps->saldo_jalan ?? 0) - ($beban->saldo_jalan ?? 0);
        $shuNonOpsAkhir = ($pendapatanNonOps->saldo_akhir ?? 0) - ($beban->saldo_akhir ?? 0);

        $totalBiayaBeban = ($biaya->saldo_akhir ?? 0) + ($beban->saldo_akhir ?? 0);
        $totalPendapatanDanN = ($pendapatanNonOps->saldo_akhir ?? 0) + ($pendapatan->saldo_akhir ?? 0);

        $shuSbPajakAwal = $shuOpsAwal + $shuNonOpsAwal;
        $shuSbPajakJalan = $shuOpsJalan + $shuNonOpsJalan;
        $shuSbPajakAkhir = $shuOpsAkhir + $shuNonOpsAkhir;

        DB::table('tabel_master')->where('unit', $unit)->where('kode_rekening', '3902000')
            ->update([
                'mut_debet' => $totalBiayaBeban,
                'mut_kredit' => $totalPendapatanDanN,
                'saldo_akhir' => $shuSbPajakAkhir,
                'saldo_jalan' => $shuSbPajakJalan,
            ]);
    }

    private function refreshTabelRugiLaba(string $unit): void
    {
        DB::table('tabel_rugi_laba')->where('unit', $unit)->delete();

        DB::statement(
            'INSERT INTO tabel_rugi_laba SELECT * FROM tabel_master WHERE gr_neraca = ? AND unit = ?',
            ['rugi-laba', $unit]
        );
    }
}
