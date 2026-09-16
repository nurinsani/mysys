<?php

namespace App\Http\Controllers;

use App\Exports\NeracaExport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportNeracaController extends BaseController
{
    public function index()
    {
        $title = 'Neraca';
        $menus = $this->getMenus();
        $data = $this->buildData(Auth::user()->unit);

        return view('admin.report_neraca.index', array_merge(compact('title', 'menus'), $data));
    }

    public function exportExcel()
    {
        $unit = Auth::user()->unit;
        $data = $this->buildData($unit);

        return Excel::download(new NeracaExport($data), 'Neraca_' . $unit . '.xlsx');
    }

    protected function buildData(string $unit): array
    {
        $aktiva = DB::table('tabel_master')
            ->where('unit', $unit)
            ->where('gr_neraca', 'AKTIVA')
            ->orderBy('kode_rekening')
            ->get();

        $totalAktiva = DB::table('tabel_master')
            ->where('unit', $unit)
            ->where('gr_neraca', 'AKTIVA')
            ->where('LINE_BALANCE', 'HEADING')
            ->selectRaw('SUM(mut_debet) as mut_debet, SUM(mut_kredit) as mut_kredit, SUM(saldo_awal) as saldo_awal, SUM(saldo_akhir) as saldo_akhir')
            ->first();

        $pasiva = DB::table('tabel_master')
            ->where('unit', $unit)
            ->where('gr_neraca', 'PASIVA')
            ->orderBy('kode_rekening')
            ->get();

        $totalPasivaKhusus = DB::table('tabel_master')
            ->where('unit', $unit)
            ->where('gr_neraca', 'PASIVA')
            ->whereIn('kode_rekening', ['3901000', '3902000', '2500000', '2611000'])
            ->selectRaw('SUM(mut_debet) as mut_debet, SUM(mut_kredit) as mut_kredit, SUM(saldo_awal) as saldo_awal, SUM(saldo_akhir) as saldo_akhir')
            ->first();

        $totalPasivaHeading = DB::table('tabel_master')
            ->where('unit', $unit)
            ->where('gr_neraca', 'PASIVA')
            ->where('LINE_BALANCE', 'HEADING')
            ->selectRaw('SUM(mut_debet) as mut_debet, SUM(mut_kredit) as mut_kredit, SUM(saldo_awal) as saldo_awal, SUM(saldo_akhir) as saldo_akhir')
            ->first();

        $totalPasiva = (object) [
            'mut_debet' => $totalPasivaKhusus->mut_debet + $totalPasivaHeading->mut_debet,
            'mut_kredit' => $totalPasivaKhusus->mut_kredit + $totalPasivaHeading->mut_kredit,
            'saldo_awal' => $totalPasivaKhusus->saldo_awal + $totalPasivaHeading->saldo_awal,
            'saldo_akhir' => $totalPasivaKhusus->saldo_akhir + $totalPasivaHeading->saldo_akhir,
        ];

        $rugiLaba = DB::table('tabel_master')
            ->where('unit', $unit)
            ->where('posisi', 'rugi-laba')
            ->where('gr_neraca', 'rugi-laba')
            ->orderBy('kode_rekening')
            ->get();

        $admin = DB::table('tabel_master')
            ->where('unit', $unit)
            ->where('posisi', 'admin')
            ->where('gr_neraca', 'admin')
            ->orderBy('kode_rekening')
            ->get();

        $pendapatanOps = DB::table('tabel_master')->where('unit', $unit)->where('kode_rekening', '40000')->first();
        $biayaOps = DB::table('tabel_master')->where('unit', $unit)->where('kode_rekening', '50000')->first();
        $pendapatanNonOps = DB::table('tabel_master')->where('unit', $unit)->where('kode_rekening', '57000')->first();
        $bebanNonOps = DB::table('tabel_master')->where('unit', $unit)->where('kode_rekening', '58000')->first();

        $shuOps = $this->selisih($pendapatanOps, $biayaOps);
        $shuNonOps = $this->selisih($pendapatanNonOps, $bebanNonOps);
        $shuSebelumPajak = $this->jumlah($shuOps, $shuNonOps);
        $estimasiPajak = (object) ['mut_debet' => 0, 'mut_kredit' => 0, 'saldo_awal' => 0, 'saldo_akhir' => 0];
        $shuSetelahPajak = $this->selisih($shuSebelumPajak, $estimasiPajak);

        return compact(
            'aktiva',
            'totalAktiva',
            'pasiva',
            'totalPasiva',
            'rugiLaba',
            'admin',
            'shuOps',
            'shuNonOps',
            'shuSebelumPajak',
            'estimasiPajak',
            'shuSetelahPajak'
        );
    }

    private function selisih($a, $b): object
    {
        return (object) [
            'mut_debet' => ($a->mut_debet ?? 0) - ($b->mut_debet ?? 0),
            'mut_kredit' => ($a->mut_kredit ?? 0) - ($b->mut_kredit ?? 0),
            'saldo_awal' => ($a->saldo_awal ?? 0) - ($b->saldo_awal ?? 0),
            'saldo_akhir' => ($a->saldo_akhir ?? 0) - ($b->saldo_akhir ?? 0),
        ];
    }

    private function jumlah($a, $b): object
    {
        return (object) [
            'mut_debet' => ($a->mut_debet ?? 0) + ($b->mut_debet ?? 0),
            'mut_kredit' => ($a->mut_kredit ?? 0) + ($b->mut_kredit ?? 0),
            'saldo_awal' => ($a->saldo_awal ?? 0) + ($b->saldo_awal ?? 0),
            'saldo_akhir' => ($a->saldo_akhir ?? 0) + ($b->saldo_akhir ?? 0),
        ];
    }
}
