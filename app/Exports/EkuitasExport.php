<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EkuitasExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $data;
    protected $tanggal_display;
    protected $total_saldo_awal;
    protected $total_penambahan;
    protected $total_saldo_akhir;

    public function __construct($data, $tanggal_display, $total_saldo_awal, $total_penambahan, $total_saldo_akhir)
    {
        $this->data = $data;
        $this->tanggal_display = $tanggal_display;
        $this->total_saldo_awal = $total_saldo_awal;
        $this->total_penambahan = $total_penambahan;
        $this->total_saldo_akhir = $total_saldo_akhir;
    }

    public function collection()
    {
        return collect([
            [
                'Saldo Awal',
                $this->data->simpanan_pokok_awal,
                $this->data->simpanan_wajib_awal,
                $this->data->hibah_awal > 0 ? $this->data->hibah_awal : '-',
                $this->data->cadangan_awal,
                $this->data->shu_awal,
                $this->total_saldo_awal
            ],
            [
                'Penambahan (Pengurangan)',
                $this->data->simpanan_pokok_penambahan != 0 ? $this->data->simpanan_pokok_penambahan : '-',
                $this->data->simpanan_wajib_penambahan != 0 ? $this->data->simpanan_wajib_penambahan : '-',
                $this->data->hibah_penambahan != 0 ? $this->data->hibah_penambahan : '-',
                $this->data->cadangan_penambahan != 0 ? $this->data->cadangan_penambahan : '-',
                $this->data->shu_penambahan != 0 ? $this->data->shu_penambahan : '-',
                $this->total_penambahan
            ],
            [
                'Saldo Akhir',
                $this->data->simpanan_pokok_akhir,
                $this->data->simpanan_wajib_akhir,
                $this->data->hibah_akhir > 0 ? $this->data->hibah_akhir : '-',
                $this->data->cadangan_akhir,
                $this->data->shu_akhir,
                $this->total_saldo_akhir
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            $this->tanggal_display,
            'Simpanan Pokok',
            'Simpanan Wajib',
            'Hibah',
            'Cadangan',
            'Akumulasi Sisa Hasil Usaha',
            'Total'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 18,
            'C' => 18,
            'D' => 15,
            'E' => 15,
            'F' => 28,
            'G' => 20,
        ];
    }

    public function title(): string
    {
        return 'Laporan Ekuitas';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E7E6E6']
            ]
        ]);

        $sheet->getStyle('A2:A4')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
        ]);

        $sheet->getStyle('G2:G4')->getFont()->setBold(true);

        $sheet->getStyle('A1:G4')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        $sheet->getStyle('B2:G4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('D2:D4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:G4')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getRowDimension(1)->setRowHeight(25);

        return [];
    }
}