<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * SENGAJA pakai FromCollection, bukan FromQuery — sudah dites & terbukti
 * Maatwebsite Excel's FromQuery MENGABAIKAN offset()/limit() manual dan
 * menerapkan pagination internalnya sendiri (setiap sheet berakhir membaca
 * dari baris pertama lagi, apa pun $index-nya). FromCollection tidak punya
 * masalah itu — collection() di sini menjalankan query offset/limit sendiri
 * lalu mengembalikan Collection langsung, jadi setiap sheet betul-betul
 * hanya memuat $chunkSize baris ke memori (bukan seluruh tabel).
 */
class BukuBesarStreamSheetExport implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithEvents
{
    protected $no_perkiraan;
    protected $tahun;
    protected $bulan;
    protected $all;
    protected $index;
    protected $chunkSize;
    protected $info;
    protected $title;
    protected $saldo;

    public function __construct($no_perkiraan, $tahun, $bulan, $all, $index, $chunkSize, $saldoAwalChunk, $info, $title)
    {
        $this->no_perkiraan = $no_perkiraan;
        $this->tahun = $tahun;
        $this->bulan = $bulan;
        $this->all = $all;
        $this->index = $index;
        $this->chunkSize = $chunkSize;
        $this->info = $info;
        $this->title = $title;
        $this->saldo = $saldoAwalChunk;
    }

    public function collection()
    {
        $offset = $this->index * $this->chunkSize;

        $rows = DB::table('tabel_transaksi')
            ->where('kode_rekening', $this->no_perkiraan)
            ->when(!$this->all && $this->tahun, fn($q) => $q->whereYear('tanggal_transaksi', $this->tahun))
            ->when(!$this->all && $this->bulan, fn($q) => $q->whereMonth('tanggal_transaksi', $this->bulan))
            ->orderBy('tanggal_transaksi', 'asc')
            ->offset($offset)
            ->limit($this->chunkSize)
            ->get(['tanggal_transaksi', 'kode_transaksi', 'kode_rekening', 'keterangan_transaksi', 'debet', 'kredit']);

        return new Collection($rows->map(function ($row) {
            if ($this->info['normal'] == 'debet') {
                $this->saldo = $this->saldo + $row->debet - $row->kredit;
            } else {
                $this->saldo = $this->saldo - $row->debet + $row->kredit;
            }

            return [
                $row->tanggal_transaksi,
                $row->kode_transaksi,
                $row->kode_rekening,
                $row->keterangan_transaksi,
                $row->debet,
                $row->kredit,
                $this->saldo,
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
            ['Saldo Awal', ':', number_format($this->info['saldo_awal'], 0, ',', '.')],
            ['Saldo Akhir', ':', number_format($this->info['saldo_akhir'], 0, ',', '.')],
            [],
            ['Tanggal', 'Nomor Bukti', 'Kode Rekening', 'Keterangan', 'Debet', 'Kredit', 'Saldo'],
        ];
    }

    public function title(): string
    {
        return $this->title;
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
            }
        ];
    }
}
