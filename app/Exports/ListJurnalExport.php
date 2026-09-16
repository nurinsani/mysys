<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * Sesi 2.6 — sebelumnya FromCollection dengan ->get() di collection(), bisa
 * berat untuk unit sibuk + rentang tanggal lebar (tabel_transaksi ada 21,8
 * juta baris). Diganti FromQuery + WithChunkReading (dibaca sebagian-sebagian
 * dari database, bukan sekaligus ke memori) — beda dari kasus BukuBesar,
 * di sini TIDAK ada offset() manual yang bikin konflik, jadi WithChunkReading
 * aman dipakai. Total debet/kredit di baris akhir dihitung lewat query
 * agregat terpisah (SUM), bukan dari akumulasi saat fetch data.
 */
class ListJurnalExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading, WithEvents, ShouldAutoSize
{
    protected $unit, $awal, $akhir;
    protected $totalDebet;
    protected $totalKredit;

    public function __construct($unit, $awal, $akhir)
    {
        $this->unit = $unit;
        $this->awal = $awal;
        $this->akhir = $akhir;

        $totals = DB::table('tabel_transaksi')
            ->where('unit', $this->unit)
            ->whereBetween('tanggal_transaksi', [$this->awal, $this->akhir])
            ->selectRaw('COALESCE(SUM(debet), 0) as total_debet, COALESCE(SUM(kredit), 0) as total_kredit')
            ->first();

        $this->totalDebet = $totals->total_debet;
        $this->totalKredit = $totals->total_kredit;
    }

    public function query()
    {
        return DB::table('tabel_transaksi')
            ->where('unit', $this->unit)
            ->whereBetween('tanggal_transaksi', [$this->awal, $this->akhir])
            ->orderBy('tanggal_transaksi', 'asc')
            ->select(
                DB::raw("DATE_FORMAT(tanggal_transaksi, '%Y-%m-%d') as tanggal_transaksi"),
                'kode_transaksi',
                'kode_rekening',
                'keterangan_transaksi',
                'jenis_transaksi',
                'debet',
                'kredit'
            );
    }

    public function chunkSize(): int
    {
        return 5000;
    }

    public function map($row): array
    {
        return [
            $row->tanggal_transaksi,
            $row->kode_transaksi,
            $row->kode_rekening,
            $row->keterangan_transaksi,
            $row->jenis_transaksi,
            $row->debet,
            $row->kredit,
        ];
    }

    public function headings(): array
    {
        return [
            ['Periode: ' . date('Y-m-d', strtotime($this->awal)) . ' s.d. ' . date('Y-m-d', strtotime($this->akhir))],
            ['Tanggal', 'Nomor Bukti', 'Kode Rekening', 'Keterangan', 'Jenis Transaksi', 'Debet', 'Kredit']
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->mergeCells('A1:G1');
                $event->sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $event->sheet->getRowDimension(1)->setRowHeight(25);

                $event->sheet->getStyle('A2:G2')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => 'center'],
                    'borders' => [
                        'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                    ],
                ]);

                $rowCount = $event->sheet->getDelegate()->getHighestRow();

                $event->sheet->getStyle('A2:G' . $rowCount)->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                    ],
                ]);

                $lastRow = $rowCount + 1;

                $event->sheet->mergeCells("A{$lastRow}:E{$lastRow}");
                $event->sheet->setCellValue("A{$lastRow}", 'Total');

                $event->sheet->setCellValue("F{$lastRow}", $this->totalDebet);
                $event->sheet->setCellValue("G{$lastRow}", $this->totalKredit);

                $event->sheet->getStyle("A{$lastRow}:G{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'borders' => [
                        'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
                    ],
                ]);

                $event->sheet->getStyle("A{$lastRow}")->applyFromArray([
                    'alignment' => ['horizontal' => 'center'],
                ]);

                $event->sheet->getStyle("F{$lastRow}:G{$lastRow}")->applyFromArray([
                    'alignment' => ['horizontal' => 'right'],
                ]);

                $event->sheet->getStyle("F{$lastRow}:G{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
            }

        ];
    }
}
