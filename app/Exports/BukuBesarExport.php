<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class BukuBesarExport implements FromCollection, WithHeadings, WithEvents
{
    protected $transaksi;
    protected $info;
    protected $title;

    public function __construct($transaksi, $info, $title)
    {
        $this->transaksi = $transaksi;
        $this->info      = $info;
        $this->title     = $title;
    }

    public function collection()
    {
        $saldo = $this->info['saldo_awal'];
        $jenis = $this->info['normal'];
        return new Collection($this->transaksi->map(function ($row) use (&$saldo,$jenis) {
            if($jenis=='debet'){
                $saldo = $saldo + $row->debet - $row->kredit ?? 0;
            } else {
                $saldo = $saldo - $row->debet + $row->kredit ?? 0;
            }

            return [
                $row->tanggal_transaksi,
                $row->kode_transaksi,
                $row->kode_rekening,
                $row->keterangan_transaksi,
                $row->debet,
                $row->kredit,
                $saldo,
            ];
        }));
    }

    public function headings(): array
    {
        return [
            ['KOPERASI SIMPAN PINJAM PEMBIAYAAN SYARIAH NURINSANI'],
            [$this->title],
            ['UNIT : ' . $this->info['unit']],
            [],
            ['No Perkiraan', ':', $this->info['kode_rekening']],
            ['Nama Perkiraan', ':', $this->info['nama_rekening']],
            ['Saldo Awal', ':', number_format($this->info['saldo_awal'], 0, ',', '.') ],
            ['Saldo Akhir', ':', number_format($this->info['saldo_akhir'], 0, ',', '.') ],
            [],
            ['Tanggal', 'Nomor Bukti', 'Kode Rekening', 'Keterangan', 'Debet', 'Kredit', 'Saldo'],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->mergeCells('A1:G1');
                $sheet->mergeCells('A2:G2');
                $sheet->mergeCells('A3:G3');

                $sheet->getStyle('A1:A3')->getFont()->setBold(true);
                $sheet->getStyle('A10:G10')->getFont()->setBold(true);

                foreach (range('A', 'G') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
        ];
    }
}
