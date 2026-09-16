<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ListJurnalExport;

class ListJurnalController extends BaseController
{
    public function index()
    {
        $title = 'List Jurnal';
        $menus = $this->getMenus();

        return view('admin.list_jurnal.index', compact('menus', 'title'));
    }

    public function getTransaksi(Request $request)
    {
        $unit = Auth::user()->unit;
        $tanggalAwal = $request->tanggal_awal;
        $tanggalAkhir = $request->tanggal_akhir;

        $data = DB::table('tabel_transaksi')
            ->where('unit', $unit)
            ->when($tanggalAwal && $tanggalAkhir, function ($query) use ($tanggalAwal, $tanggalAkhir) {
                $query->whereBetween('tanggal_transaksi', [$tanggalAwal, $tanggalAkhir]);
            })
            ->select(
                'unit',
                DB::raw('MIN(tanggal_transaksi) as tanggal_awal'),
                DB::raw('MAX(tanggal_transaksi) as tanggal_akhir'),
                DB::raw('SUM(debet) as total_debet'),
                DB::raw('SUM(kredit) as total_kredit')
            )
            ->groupBy('unit')
            ->get();

        return response()->json(['data' => $data]);
    }

    /**
     * Di atas ambang ini, export dialihkan ke CSV streaming (streamCsv())
     * bukan ditolak — lihat catatan lengkap di
     * BukuBesarController::MAKS_BARIS_EXPORT_XLSX (alasan yang sama:
     * PhpSpreadsheet selalu menyimpan semua sel di memori dulu, CSV
     * streaming langsung via fputcsv + cursor() tidak).
     */
    private const MAKS_BARIS_EXPORT_XLSX = 200000;

    public function export(Request $request)
    {
        $unit = $request->unit;
        $awal = $request->tanggal_awal;
        $akhir = $request->tanggal_akhir;

        $jumlahBaris = DB::table('tabel_transaksi')
            ->where('unit', $unit)
            ->whereBetween('tanggal_transaksi', [$awal, $akhir])
            ->count();

        if ($jumlahBaris > self::MAKS_BARIS_EXPORT_XLSX) {
            return $this->streamCsv($unit, $awal, $akhir);
        }

        return Excel::download(new ListJurnalExport($unit, $awal, $akhir), 'list_jurnal_'.$unit.'.xlsx');
    }

    /**
     * Streaming CSV murni (fputcsv + DB cursor()) — tanpa PhpSpreadsheet,
     * tidak ada batas jumlah baris yang aman secara memori. Total
     * debet/kredit dihitung lewat query SUM() terpisah sebelum streaming
     * mulai (murah, tidak menarik baris ke PHP), ditulis di baris terakhir.
     */
    private function streamCsv(string $unit, ?string $awal, ?string $akhir)
    {
        $fileName = 'list_jurnal_' . $unit . '.csv';

        $totals = DB::table('tabel_transaksi')
            ->where('unit', $unit)
            ->whereBetween('tanggal_transaksi', [$awal, $akhir])
            ->selectRaw('COALESCE(SUM(debet), 0) as total_debet, COALESCE(SUM(kredit), 0) as total_kredit')
            ->first();

        return response()->streamDownload(function () use ($unit, $awal, $akhir, $totals) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Periode: ' . date('Y-m-d', strtotime($awal)) . ' s.d. ' . date('Y-m-d', strtotime($akhir))]);
            fputcsv($out, ['Tanggal', 'Nomor Bukti', 'Kode Rekening', 'Keterangan', 'Jenis Transaksi', 'Debet', 'Kredit']);

            DB::table('tabel_transaksi')
                ->where('unit', $unit)
                ->whereBetween('tanggal_transaksi', [$awal, $akhir])
                ->orderBy('tanggal_transaksi', 'asc')
                ->select(
                    DB::raw("DATE_FORMAT(tanggal_transaksi, '%Y-%m-%d') as tanggal_transaksi"),
                    'kode_transaksi',
                    'kode_rekening',
                    'keterangan_transaksi',
                    'jenis_transaksi',
                    'debet',
                    'kredit'
                )
                ->cursor()
                ->each(function ($row) use ($out) {
                    fputcsv($out, [
                        $row->tanggal_transaksi,
                        $row->kode_transaksi,
                        $row->kode_rekening,
                        $row->keterangan_transaksi,
                        $row->jenis_transaksi,
                        $row->debet,
                        $row->kredit,
                    ]);
                });

            fputcsv($out, ['Total', '', '', '', '', $totals->total_debet, $totals->total_kredit]);

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

}
