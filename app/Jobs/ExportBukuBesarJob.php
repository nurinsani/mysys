<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class ExportBukuBesarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $no_perkiraan;
    protected $tahun;
    protected $bulan;
    protected $all;
    protected $userEmail;
    protected $exportId;

    public function __construct($no_perkiraan, $tahun = null, $bulan = null, $all = false, $userEmail = null, $exportId = null)
    {
        $this->no_perkiraan = $no_perkiraan;
        $this->tahun = $tahun;
        $this->bulan = $bulan;
        $this->all = $all;
        $this->userEmail = $userEmail;
        $this->exportId = $exportId;
    }

    public function handle()
    {
        $no_perkiraan = $this->no_perkiraan;
        $tahun = $this->tahun;
        $bulan = $this->bulan;
        $all = $this->all;

        $query = DB::table('tabel_transaksi')
            ->select('tanggal_transaksi', 'kode_transaksi', 'kode_rekening', 'keterangan_transaksi', 'debet', 'kredit', 'branch.unit as unit')
            ->leftJoin('branch', 'branch.kode_branch', '=', 'tabel_transaksi.unit')
            ->where('kode_rekening', $no_perkiraan);

        if (!$all) {
            if ($tahun) $query->whereYear('tanggal_transaksi', $tahun);
            if ($bulan) $query->whereMonth('tanggal_transaksi', $bulan);
        }

        $akun = DB::table('tabel_master')
            ->select('kode_rekening', 'nama_rekening', 'saldo_awal', 'saldo_akhir', 'normal')
            ->where('kode_rekening', $no_perkiraan)
            ->first();

        $path = storage_path('app/exports');
        if (!is_dir($path)) mkdir($path, 0755, true);

        $csvFile = "{$path}/buku_besar_{$no_perkiraan}.csv";
        $zipFile = "{$path}/buku_besar_{$no_perkiraan}.zip";

        $handle = fopen($csvFile, 'w');
        fputcsv($handle, ['No Perkiraan', '', $akun->kode_rekening ?? '-']);
        fputcsv($handle, ['Nama Perkiraan', '', $akun->nama_rekening ?? '-']);
        fputcsv($handle, ['Saldo Awal', '', $akun->saldo_awal ?? 0]);
        fputcsv($handle, ['Saldo Akhir', '', $akun->saldo_akhir ?? 0]);
        fputcsv($handle, []);
        fputcsv($handle, []);
        fputcsv($handle, ['Tanggal', 'Unit', 'Nomor Bukti', 'Kode Rekening', 'Keterangan', 'Debet', 'Kredit', 'Saldo']);

        $saldo = $akun->saldo_awal ?? 0;
        $jenis = strtolower($akun->normal ?? 'debet');

        $query->orderBy('tanggal_transaksi')->chunk(10000, function ($rows) use ($handle, &$saldo, $jenis) {
            foreach ($rows as $row) {
                if ($jenis === 'debet') {
                    $saldo = $saldo + (($row->debet ?? 0) - ($row->kredit ?? 0));
                } else {
                    $saldo = $saldo - (($row->debet ?? 0) - ($row->kredit ?? 0));
                }

                fputcsv($handle, [
                    $row->tanggal_transaksi,
                    $row->unit,
                    $row->kode_transaksi,
                    $row->kode_rekening,
                    $row->keterangan_transaksi,
                    $row->debet,
                    $row->kredit,
                    $saldo,
                ]);
            }
        });

        fclose($handle);

        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            $zip->addFile($csvFile, basename($csvFile));
            $zip->close();
        }

        unlink($csvFile);

        if ($this->exportId) {
            DB::table('exports')
                ->where('id', $this->exportId)
                ->update([
                    'status' => 'done',
                    'file_name' => basename($zipFile),
                    'updated_at' => now(),
                ]);
        }
    }
}
