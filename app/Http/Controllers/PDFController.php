<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use NumberFormatter;

class PDFController extends Controller
{
    public function generateMusyarakahPdf(Request $request, $feature, $date)
    {
        $viewPath = "admin.{$feature}.template";

        if (!view()->exists($viewPath)) {
            abort(404, 'Template not found');
        }

        $unit = $request->unit;
        $tanggalCetak = \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d');

        $results = DB::table('temp_akad_mus')
            ->leftJoin('anggota', 'temp_akad_mus.cif', '=', 'anggota.cif')
            ->leftJoin('ao', 'anggota.cao', '=', 'ao.cao')
            ->leftJoin('mm', 'ao.atasan', '=', 'mm.nik')
            ->where([
                ['temp_akad_mus.tgl_akad', $tanggalCetak],
                ['temp_akad_mus.unit', $unit]
            ])
            ->orderBy('temp_akad_mus.code_kel')
            ->select(
                'temp_akad_mus.*',
                'anggota.ktp as ktp',
                'anggota.desa as desa',
                'anggota.kecamatan as kecamatan',
                'anggota.nama as anggota_nama',
                'ao.atasan as atasan',
                'mm.nama as mm_nama',
                'mm.*'
            )
            ->get();

        foreach ($results as $result) {
            $tglAkad = \Carbon\Carbon::parse($result->tgl_akad)->locale('id');
            $result->tanggal = $tglAkad->day;
            $result->bulan = $tglAkad->translatedFormat('F');
            $result->tahun = $tglAkad->year;
            $dayNames = [
                0 => 'Minggu',
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
            ];
            $result->namaHari = $dayNames[$tglAkad->dayOfWeek];

            $maturityDate = \Carbon\Carbon::parse($result->maturity_date)->locale('id');
            $result->maturityTanggal = $maturityDate->day;
            $result->maturityBulan = $maturityDate->translatedFormat('F');
            $result->maturityTahun = $maturityDate->year;

            $result->totalPinjaman = 'Rp. ' . number_format($result->plafond, 0, ',', '.');
            $result->nominalAngsuran = 'Rp. ' . number_format($result->bulat, 0, ',', '.');

            $result->totalPinjamanText = $this->convertToRupiahText($result->plafond);
            $result->nominalAngsuranText = $this->convertToRupiahText($result->bulat);

            $result->persentaseMarginNI = ($result->persen_margin * 100) . '%';

            $marginValue = $result->persen_margin * 100;
            $result->persentaseMargin = (100 - $marginValue) . '%';

            $result->ktp = $result->ktp ?? null;
            $result->desa = $result->desa ?? null;
            $result->kecamatan = $result->kecamatan ?? null;
            $result->namaMM = $result->mm_nama ?? null;
            $result->anggotaNama = $result->anggota_nama ?? null;
        }

        if ($results->isEmpty()) {
            abort(404, 'No records found for the specified dates.');
        }

        $data = ['results' => $results];

        $pdf = PDF::loadView($viewPath, $data);

        return $pdf->stream("{$feature}_{$tanggalCetak}_combined.pdf");
    }

    public function generateLaRisywahPdf(Request $request, $feature, $kelompok, $date)
    {
        $viewPath = "admin.{$feature}.template";

        if (!view()->exists($viewPath)) {
            abort(404, 'Template not found');
        }

        $unit = $request->unit;
        $tanggalCetak = \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d');

        $results = DB::table('temp_akad_mus')
            ->leftJoin('kelompok', 'temp_akad_mus.code_kel', '=', 'kelompok.code_kel')
            ->where([
                ['temp_akad_mus.tgl_akad', $tanggalCetak],
                ['temp_akad_mus.code_kel', $kelompok],
                ['temp_akad_mus.unit', $unit],
                ['temp_akad_mus.status_app', 'MUSYARAKAH']
            ])
            ->select(
                'temp_akad_mus.*',
                'kelompok.nama_kel as kelompok_name'
            )
            ->get();

        foreach ($results as $result) {
            $result->formattedPlafond = 'Rp. ' . number_format($result->plafond, 0, ',', '.');
        }

        if ($results->isEmpty()) {
            abort(404, 'No records found for the specified date and kelompok.');
        }

        $namaKel = $results->first()->kelompok_name;

        $totalPlafond = $results->sum('plafond');
        $formattedTotalPlafond = 'Rp. ' . number_format($totalPlafond, 0, ',', '.');

        $data = [
            'results' => $results,
            'totalPlafond' => $formattedTotalPlafond,
            'namaKelompok' => $namaKel
        ];

        $pdf = PDF::loadView($viewPath, $data);

        return $pdf->stream("{$feature}_{$kelompok}_{$tanggalCetak}.pdf");
    }

    public function generateApprovalPdf(Request $request, $feature, $date)
    {
        $viewPath = "admin.{$feature}.template";
        abort_unless(view()->exists($viewPath), 404, 'Template not found');

        $unit = $request->unit;
        $tanggalMurab = \Carbon\Carbon::parse($date)->format('Y-m-d H:i:s');

        $namaMM = DB::table('mm')->where('unit', $unit)->value('nama');

        $anggota = DB::table('temp_akad_mus')
            ->leftJoin('anggota', 'temp_akad_mus.cif', '=', 'anggota.cif')
            ->leftJoinSub(
                DB::table('rekomendasi')
                    ->select('cif', DB::raw('MAX(nominal) AS nominal'))
                    ->groupBy('cif'),
                'rekomendasi',
                'temp_akad_mus.cif',
                '=',
                'rekomendasi.cif'
            )
            ->where([
                ['temp_akad_mus.tgl_murab', $tanggalMurab],
                ['temp_akad_mus.unit', $unit]
            ])
            ->select(
                'temp_akad_mus.*',
                'anggota.cif AS cif_anggota',
                'anggota.nama AS nama_anggota',
                'anggota.kode_kel',
                'anggota.kota',
                'anggota.kecamatan',
                'anggota.desa',
                'anggota.waris',
                'rekomendasi.nominal'
            )
            ->get()
            ->map(function ($item) {
                $item->plafond = 'Rp. ' . number_format($item->plafond, 0, ',', '.');
                $item->nominal = 'Rp. ' . number_format($item->nominal, 0, ',', '.');

                $item->tgl_akad = \Carbon\Carbon::parse($item->tgl_akad)->translatedFormat('d F Y');
                $item->tgl_wakalah = $item->tgl_akad;
                return $item;
            });

        abort_if($anggota->isEmpty(), 404, 'No records found for the specified date');

        $totalPlafond = 'Rp. ' . number_format(
            $anggota->sum(fn($a) => (int) str_replace(['Rp. ', '.'], '', $a->plafond)),
            0,
            ',',
            '.'
        );

        $totalAnggota = $anggota->count();

        $groupedData = $anggota->groupBy('kode_kel')->map(function ($items) {
            return [
                'count' => $items->count(),
                'total_plafond' => 'Rp. ' . number_format(
                    $items->sum(fn($a) => (int) str_replace(['Rp. ', '.'], '', $a->plafond)),
                    0,
                    ',',
                    '.'
                ),
            ];
        });

        $kelompok = DB::table('kelompok')
            ->leftJoin('ao', 'ao.cao', '=', 'kelompok.cao')
            ->whereIn('code_kel', $groupedData->keys())
            ->select('kelompok.*', 'ao.nama_ao')
            ->get()
            ->map(function ($item) use ($groupedData) {
                $kodeKel = $item->code_kel;
                $item->count = $groupedData[$kodeKel]['count'] ?? 0;
                $item->total_plafond = $groupedData[$kodeKel]['total_plafond'] ?? 'Rp. 0';
                return $item;
            });

        return PDF::loadView($viewPath, compact('anggota', 'kelompok', 'totalPlafond', 'totalAnggota', 'namaMM', 'unit'))
            ->setPaper('a4', 'landscape')
            ->stream("{$feature}_{$tanggalMurab}.pdf");
    }

    private function convertToRupiahText($number)
    {
        $f = new NumberFormatter('id_ID', NumberFormatter::SPELLOUT);
        $text = $f->format($number);
        return ucfirst($text) . ' Rupiah';
    }

}
