<?php

namespace App\Http\Controllers;

use App\Exports\PpapExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportPpapController extends BaseController
{
    public function index()
    {
        $title = 'Report PPAP';
        $menus = $this->getMenus();

        return view('admin.report_ppap.index', compact('menus', 'title'));
    }

    public function cari(Request $request)
    {
        $request->validate([
            'jenis_kolek' => 'required|string'
        ]);

        $unit = Auth::user()->unit;
        $jenisKolek = $request->jenis_kolek;

        $tunggakan = DB::table('pembiayaan')
            ->join('kelompok', 'pembiayaan.code_kel', '=', 'kelompok.code_kel')
            ->join('ao', 'pembiayaan.cao', '=', 'ao.cao')
            ->leftJoin('tunggakan', 'pembiayaan.cif', '=', 'tunggakan.cif')
            ->where('pembiayaan.unit', $unit)
            ->select(
                'pembiayaan.unit',
                'pembiayaan.nama as nama_anggota',
                'pembiayaan.cif',
                'pembiayaan.plafond',
                'pembiayaan.os',
                'ao.nama_ao',
                'ao.cao',
                'kelompok.nama_kel as nama_kelompok',
                'kelompok.code_kel',
                DB::raw('COALESCE(SUM(tunggakan.kredit - tunggakan.debet), 0) as total_tunggakan'),
                DB::raw("
                    GREATEST(
                        COUNT(DISTINCT CASE WHEN tunggakan.kredit > 0 THEN tunggakan.tgl_tunggak END) 
                        - 
                        COUNT(DISTINCT CASE WHEN tunggakan.debet > 0 THEN tunggakan.tgl_tunggak END),
                        0
                    ) as ft
                ")
            )
            ->groupBy(
                'pembiayaan.unit',
                'pembiayaan.nama',
                'pembiayaan.cif',
                'pembiayaan.cao',
                'pembiayaan.plafond',
                'pembiayaan.os',
                'ao.nama_ao',
                'ao.cao',
                'kelompok.nama_kel',
                'kelompok.code_kel'
            )
            ->havingRaw('total_tunggakan > 0');

        // filter berdasarkan jenis kolektibilitas berdasarkan FT (Frekuensi Tunggakan)
        switch($jenisKolek) {
            case 'semua':
                break;
            case 'lancar':
                $tunggakan->havingRaw('ft BETWEEN 1 AND 3');
                break;
            case 'kurang_lancar':
                $tunggakan->havingRaw('ft BETWEEN 4 AND 6');
                break;
            case 'diragukan':
                $tunggakan->havingRaw('ft BETWEEN 7 AND 11');
                break;
            case 'macet':
                $tunggakan->havingRaw('ft >= 12');
                break;
        }

        $data = $tunggakan->get();

        $hasil = [
            'unit' => $unit,
            'total_noa' => $data->count(),
            'plafond' => $data->sum('plafond'),
            'os' => $data->sum('os'),
        ];

        return response()->json([
            'success' => true,
            'data' => [$hasil],
            'jenis_kolek' => $jenisKolek
        ]);
    }

    public function exportPdf(Request $request)
    {
        Carbon::setLocale('id');
        $unit = Auth::user()->unit;
        $tanggalCetak = $request->tanggal_cetak;
        $jenisKolek = $request->jenis_kolek;

        $tunggakan = DB::table('pembiayaan')
            ->join('kelompok', 'pembiayaan.code_kel', '=', 'kelompok.code_kel')
            ->join('ao', 'pembiayaan.cao', '=', 'ao.cao')
            ->leftJoin('tunggakan', 'pembiayaan.cif', '=', 'tunggakan.cif')
            ->where('pembiayaan.unit', $unit)
            ->select(
                'pembiayaan.unit',
                'pembiayaan.nama as nama_anggota',
                'pembiayaan.cif',
                'pembiayaan.plafond',
                'pembiayaan.os',
                'ao.nama_ao',
                'ao.cao',
                'kelompok.nama_kel as nama_kelompok',
                'kelompok.code_kel',
                DB::raw('COALESCE(SUM(tunggakan.kredit - tunggakan.debet), 0) as total_tunggakan'),
                DB::raw("
                    GREATEST(
                        COUNT(DISTINCT CASE WHEN tunggakan.kredit > 0 THEN tunggakan.tgl_tunggak END) 
                        - 
                        COUNT(DISTINCT CASE WHEN tunggakan.debet > 0 THEN tunggakan.tgl_tunggak END),
                        0
                    ) as ft
                ")
            )
            ->groupBy(
                'pembiayaan.unit',
                'pembiayaan.nama',
                'pembiayaan.cif',
                'pembiayaan.cao',
                'pembiayaan.plafond',
                'pembiayaan.os',
                'ao.nama_ao',
                'ao.cao',
                'kelompok.nama_kel',
                'kelompok.code_kel'
            )
            ->havingRaw('total_tunggakan > 0');

        switch($jenisKolek) {
            case 'semua':
                break;
            case 'lancar':
                $tunggakan->havingRaw('ft BETWEEN 1 AND 3');
                break;
            case 'kurang_lancar':
                $tunggakan->havingRaw('ft BETWEEN 4 AND 6');
                break;
            case 'diragukan':
                $tunggakan->havingRaw('ft BETWEEN 7 AND 11');
                break;
            case 'macet':
                $tunggakan->havingRaw('ft >= 12');
                break;
        }

        $data = $tunggakan->get();

        $dataKolektibilitas = [
            'lancar' => $data->filter(fn($item) => $item->ft >= 1 && $item->ft <= 3),
            'kurang_lancar' => $data->filter(fn($item) => $item->ft >= 4 && $item->ft <= 6),
            'diragukan' => $data->filter(fn($item) => $item->ft >= 7 && $item->ft <= 11),
            'macet' => $data->filter(fn($item) => $item->ft >= 12),
        ];

        $summary = [
            'lancar' => [
                'jumlah_debitur' => $dataKolektibilitas['lancar']->count(),
                'plafond' => $dataKolektibilitas['lancar']->sum('plafond'),
                'saldo_pinjaman' => $dataKolektibilitas['lancar']->sum('os'),
                'ppap' => $dataKolektibilitas['lancar']->sum('os') * 0.005, // 0.5%
            ],
            'kurang_lancar' => [
                'jumlah_debitur' => $dataKolektibilitas['kurang_lancar']->count(),
                'plafond' => $dataKolektibilitas['kurang_lancar']->sum('plafond'),
                'saldo_pinjaman' => $dataKolektibilitas['kurang_lancar']->sum('os'),
                'ppap' => $dataKolektibilitas['kurang_lancar']->sum('os') * 0.10, // 10%
            ],
            'diragukan' => [
                'jumlah_debitur' => $dataKolektibilitas['diragukan']->count(),
                'plafond' => $dataKolektibilitas['diragukan']->sum('plafond'),
                'saldo_pinjaman' => $dataKolektibilitas['diragukan']->sum('os'),
                'ppap' => $dataKolektibilitas['diragukan']->sum('os') * 0.50, // 50%
            ],
            'macet' => [
                'jumlah_debitur' => $dataKolektibilitas['macet']->count(),
                'plafond' => $dataKolektibilitas['macet']->sum('plafond'),
                'saldo_pinjaman' => $dataKolektibilitas['macet']->sum('os'),
                'ppap' => $dataKolektibilitas['macet']->sum('os') * 1.00, // 100%
            ],
        ];

        $total = [
            'jumlah_debitur' => array_sum(array_column($summary, 'jumlah_debitur')),
            'plafond' => array_sum(array_column($summary, 'plafond')),
            'saldo_pinjaman' => array_sum(array_column($summary, 'saldo_pinjaman')),
            'ppap' => array_sum(array_column($summary, 'ppap')),
        ];

        $branch = DB::table('branch')->where('kode_branch', $unit)->first();

        $pdf = Pdf::loadView('admin.report_ppap.pdf', [
            'summary' => $summary,
            'total' => $total,
            'tanggal_cetak' => $tanggalCetak,
            'branch' => $branch,
            'jenisKolek' => $jenisKolek
        ]);

        $pdf->setPaper('A4', 'landscape');
        
        return $pdf->stream('Rekap_Kolektabilitas_' . $unit . '_' . date('Ymd') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $unit = Auth::user()->unit;
        $tanggalCetak = $request->tanggal_cetak;
        $jenisKolek = $request->jenis_kolek;

        $tunggakan = DB::table('pembiayaan')
            ->join('kelompok', 'pembiayaan.code_kel', '=', 'kelompok.code_kel')
            ->join('ao', 'pembiayaan.cao', '=', 'ao.cao')
            ->leftJoin('tunggakan', 'pembiayaan.cif', '=', 'tunggakan.cif')
            ->where('pembiayaan.unit', $unit)
            ->select(
                'pembiayaan.unit',
                'pembiayaan.no_anggota',
                'pembiayaan.nama as nama_anggota',
                'pembiayaan.code_kel',
                'pembiayaan.tgl_murab as tanggal_pk',
                'pembiayaan.tenor',
                'pembiayaan.maturity_date',
                'pembiayaan.plafond',
                'pembiayaan.os',
                'pembiayaan.os',
                'pembiayaan.saldo_margin',
                'pembiayaan.pokok',
                'pembiayaan.ijaroh',
                'pembiayaan.gol',
                'pembiayaan.cao',
                'ao.nama_ao',
                'kelompok.nama_kel as nama_kelompok',
                DB::raw('COALESCE(SUM(tunggakan.kredit - tunggakan.debet), 0) as total_tunggakan'),
                DB::raw('GREATEST(COALESCE(SUM(CASE WHEN tunggakan.kredit = pembiayaan.pokok THEN tunggakan.kredit ELSE 0 END), 0) - pembiayaan.pokok, 0) as tunggakan_pokok'),
                DB::raw('GREATEST(COALESCE(SUM(CASE WHEN tunggakan.kredit = (pembiayaan.saldo_margin - pembiayaan.bulat) THEN tunggakan.kredit ELSE 0 END), 0) - (pembiayaan.saldo_margin - pembiayaan.bulat), 0) as tunggakan_margin'),
                DB::raw("
                    GREATEST(
                        COUNT(DISTINCT CASE WHEN tunggakan.kredit > 0 THEN tunggakan.tgl_tunggak END) 
                        - 
                        COUNT(DISTINCT CASE WHEN tunggakan.debet > 0 THEN tunggakan.tgl_tunggak END),
                        0
                    ) as ft
                ")
            )
            ->groupBy(
                'pembiayaan.unit',
                'pembiayaan.nama',
                'pembiayaan.cif',
                'pembiayaan.plafond',
                'pembiayaan.os',
                'pembiayaan.tgl_akad',
                'pembiayaan.tenor',
                'pembiayaan.maturity_date',
                'ao.nama_ao',
                'ao.cao',
                'kelompok.nama_kel',
            )
            ->havingRaw('total_tunggakan > 0');

        switch($jenisKolek) {
            case 'semua':
                break;
            case 'lancar':
                $tunggakan->havingRaw('ft BETWEEN 1 AND 3');
                break;
            case 'kurang_lancar':
                $tunggakan->havingRaw('ft BETWEEN 4 AND 6');
                break;
            case 'diragukan':
                $tunggakan->havingRaw('ft BETWEEN 7 AND 11');
                break;
            case 'macet':
                $tunggakan->havingRaw('ft >= 12');
                break;
        }

        $data = $tunggakan->get();

        $dataKolektibilitas = [
            'lancar' => $data->filter(fn($item) => $item->ft >= 1 && $item->ft <= 3),
            'kurang_lancar' => $data->filter(fn($item) => $item->ft >= 4 && $item->ft <= 6),
            'diragukan' => $data->filter(fn($item) => $item->ft >= 7 && $item->ft <= 11),
            'macet' => $data->filter(fn($item) => $item->ft >= 12),
        ];

        $fileName = 'Kolek' . ' ' . date('Ymd') . ' ' . $unit . '.xlsx';

        return Excel::download(new PpapExport($dataKolektibilitas, $jenisKolek), $fileName);
    }
}
