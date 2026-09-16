<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BukuBesarMultiSheetExport implements WithMultipleSheets
{
    protected $no_perkiraan;
    protected $tahun;
    protected $bulan;
    protected $all;
    protected $info;
    protected $title;
    protected $chunkSize;

    /**
     * Sesi 2.6 — sengaja TIDAK menerima Collection lagi. Sebelumnya controller
     * memanggil $query->get() dulu (bisa jutaan baris untuk akun tersibuk,
     * 6,6 juta baris untuk kode_rekening 2101000) baru diserahkan ke sini —
     * itu yang bikin export bisa kehabisan memori. Sekarang query dijalankan
     * per-chunk secara lazy lewat BukuBesarStreamSheetExport (FromQuery +
     * WithChunkReading), dipecah otomatis jadi beberapa sheet kalau jumlah
     * baris melebihi $chunkSize (batas hard Excel per sheet: 1.048.576 baris).
     */
    public function __construct($no_perkiraan, $tahun, $bulan, $all, $info, $title, $chunkSize = 150000)
    {
        $this->no_perkiraan = $no_perkiraan;
        $this->tahun = $tahun;
        $this->bulan = $bulan;
        $this->all = $all;
        $this->info = $info;
        $this->title = $title;
        $this->chunkSize = $chunkSize;
    }

    public function sheets(): array
    {
        $totalRows = $this->baseQuery()->count();
        $sheetsNeeded = max(1, (int) ceil($totalRows / $this->chunkSize));

        $sheets = [];
        $saldoAwalChunk = $this->info['saldo_awal'];

        for ($index = 0; $index < $sheetsNeeded; $index++) {
            $sheets[] = new BukuBesarStreamSheetExport(
                $this->no_perkiraan,
                $this->tahun,
                $this->bulan,
                $this->all,
                $index,
                $this->chunkSize,
                $saldoAwalChunk,
                $this->info,
                $this->title . ' - Part ' . ($index + 1)
            );

            if ($index < $sheetsNeeded - 1) {
                $saldoAwalChunk = $this->saldoSetelahChunk($saldoAwalChunk, $index);
            }
        }

        return $sheets;
    }

    private function baseQuery()
    {
        return DB::table('tabel_transaksi')
            ->where('kode_rekening', $this->no_perkiraan)
            ->when(!$this->all && $this->tahun, fn($q) => $q->whereYear('tanggal_transaksi', $this->tahun))
            ->when(!$this->all && $this->bulan, fn($q) => $q->whereMonth('tanggal_transaksi', $this->bulan));
    }

    /**
     * Hitung saldo akhir 1 chunk tanpa menarik baris datanya ke PHP — cuma
     * SUM(debet)/SUM(kredit) untuk baris di rentang offset/limit chunk itu,
     * dijalankan di sisi database.
     */
    private function saldoSetelahChunk($saldoAwal, int $chunkIndex): float
    {
        $sub = $this->baseQuery()
            ->orderBy('tanggal_transaksi', 'asc')
            ->offset($chunkIndex * $this->chunkSize)
            ->limit($this->chunkSize)
            ->select('debet', 'kredit');

        $totals = DB::query()->fromSub($sub, 't')
            ->selectRaw('COALESCE(SUM(debet), 0) as total_debet, COALESCE(SUM(kredit), 0) as total_kredit')
            ->first();

        if ($this->info['normal'] == 'debet') {
            return $saldoAwal + $totals->total_debet - $totals->total_kredit;
        }

        return $saldoAwal - $totals->total_debet + $totals->total_kredit;
    }
}
