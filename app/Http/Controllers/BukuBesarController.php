<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BukuBesarMultiSheetExport;

class BukuBesarController extends BaseController
{
       public function index()
    {
        $menus = $this->getMenus();
        $data = DB::table('pull_data')->get();
        $title = 'Buku Besar';

        return view('admin.buku_besar.index',compact('menus','title','data'));

    }



       public function proses(Request $request)
    {
        $jenis_pull   = $request->jenis_pull;
        $bulan        = $request->jenis_transaksi;
        $tahun        = $request->tahun;
        $coa          = $request->kode_rekening;
        $all_data     = $request->all_data;
         $menus = $this->getMenus();
          $title = 'Buku Besar';


        $unit = auth()->user()->unit ?? '1010';
        $query = DB::table('tabel_master');

        if ($coa) {
            $query->where('kode_rekening', 'like', "%$coa%")
                  ->where('unit',$unit)
                  ->orWhere('nama_rekening', 'like', "%$coa%");
        }

        $data = $query->select('kode_rekening','nama_rekening','saldo_awal','saldo_akhir')
                      ->get();

        return view('admin.buku_besar.index', compact('data'))
            ->with('tahun', $tahun)
            ->with('bulan', $bulan)
            ->with('menus', $menus)
            ->with('title', $title)
            ->with('all_data', $all_data);

    }

    /**
     * Sesi 2.6 — di atas ambang ini, export dialihkan ke CSV streaming
     * (streamCsv()) bukan ditolak. Alasan: PhpSpreadsheet (dipakai untuk
     * .xlsx) selalu menyimpan representasi SEMUA sel di memori sebelum
     * menulis file, apa pun strategi chunk-nya — sudah dites, ~45rb baris
     * aman, ~452rb baris bikin proses PHP mati kehabisan memori. CSV yang
     * ditulis langsung (fputcsv + DB cursor(), tanpa lewat PhpSpreadsheet
     * sama sekali) tidak punya batasan itu — memori konstan berapa pun
     * jumlah barisnya. Trade-off: kehilangan styling Excel (bold, merge,
     * warna) untuk kasus yang melebihi ambang ini.
     */
    private const MAKS_BARIS_EXPORT_XLSX = 200000;

    public function download(Request $request, $no_perkiraan)
   {
        $tahun = $request->get('tahun');
        $bulan = $request->get('bulan');
        $all   = $request->get('all_data');

        $akun = DB::table('tabel_master')->where('kode_rekening', $no_perkiraan)->first();

        $info = [
            'unit'          => '004',
            'kode_rekening'  => $akun->kode_rekening ?? $no_perkiraan,
            'nama_rekening'=> $akun->nama_rekening ?? '-',
            'saldo_awal'    => $akun->saldo_awal ?? 0,
            'saldo_akhir'   => $akun->saldo_akhir ?? 0,
            'normal'    => $akun->normal,
        ];



        $title = "Laporan Buku Besar: {$no_perkiraan}";
        if (!$all && $bulan && $tahun) {
            $title .= " - Periode {$bulan}/{$tahun}";
        } elseif (!$all && $tahun) {
            $title .= " - Tahun {$tahun}";
        } else {
            $title .= " - Semua Data";
        }

        $jumlahBaris = DB::table('tabel_transaksi')
            ->where('kode_rekening', $no_perkiraan)
            ->when(!$all && $tahun, fn($q) => $q->whereYear('tanggal_transaksi', $tahun))
            ->when(!$all && $bulan, fn($q) => $q->whereMonth('tanggal_transaksi', $bulan))
            ->count();

        if ($jumlahBaris > self::MAKS_BARIS_EXPORT_XLSX) {
            return $this->streamCsv($no_perkiraan, $tahun, $bulan, $all, $info, $title);
        }

        return Excel::download(
            new BukuBesarMultiSheetExport($no_perkiraan, $tahun, $bulan, $all, $info, $title),
            "buku_besar_{$no_perkiraan}.xlsx"
        );
    }

    /**
     * Streaming CSV murni (fputcsv + DB cursor()) — tanpa PhpSpreadsheet
     * sama sekali, jadi tidak ada batas jumlah baris yang aman secara
     * memori. Saldo berjalan dihitung sambil jalan, sama seperti versi
     * .xlsx, cuma tidak ada styling.
     */
    private function streamCsv(string $no_perkiraan, ?string $tahun, ?string $bulan, $all, array $info, string $title)
    {
        $fileName = "buku_besar_{$no_perkiraan}.csv";

        return response()->streamDownload(function () use ($no_perkiraan, $tahun, $bulan, $all, $info, $title) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['KOPERASI SIMPAN PINJAM PEMBIAYAAN SYARIAH NURINSANI']);
            fputcsv($out, [$title]);
            fputcsv($out, ['UNIT : ' . $info['unit']]);
            fputcsv($out, []);
            fputcsv($out, ['No Perkiraan', $info['kode_rekening']]);
            fputcsv($out, ['Nama Perkiraan', $info['nama_rekening']]);
            fputcsv($out, ['Saldo Awal', $info['saldo_awal']]);
            fputcsv($out, ['Saldo Akhir', $info['saldo_akhir']]);
            fputcsv($out, []);
            fputcsv($out, ['Tanggal', 'Nomor Bukti', 'Kode Rekening', 'Keterangan', 'Debet', 'Kredit', 'Saldo']);

            $saldo = $info['saldo_awal'];

            DB::table('tabel_transaksi')
                ->where('kode_rekening', $no_perkiraan)
                ->when(!$all && $tahun, fn($q) => $q->whereYear('tanggal_transaksi', $tahun))
                ->when(!$all && $bulan, fn($q) => $q->whereMonth('tanggal_transaksi', $bulan))
                ->orderBy('tanggal_transaksi', 'asc')
                ->select('tanggal_transaksi', 'kode_transaksi', 'kode_rekening', 'keterangan_transaksi', 'debet', 'kredit')
                ->cursor()
                ->each(function ($row) use ($out, $info, &$saldo) {
                    if ($info['normal'] == 'debet') {
                        $saldo = $saldo + $row->debet - $row->kredit;
                    } else {
                        $saldo = $saldo - $row->debet + $row->kredit;
                    }

                    fputcsv($out, [
                        $row->tanggal_transaksi,
                        $row->kode_transaksi,
                        $row->kode_rekening,
                        $row->keterangan_transaksi,
                        $row->debet,
                        $row->kredit,
                        $saldo,
                    ]);
                });

            fclose($out);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function suggest(Request $request)
    {
        $q = $request->get('q');
        $unit = auth()->user()->unit ?? '1010';

        $result = DB::table('tabel_master')
            ->where('kode_rekening', 'like', "%$q%")
            ->orWhere('nama_rekening', 'like', "%$q%")
            ->where('unit',$unit)
            ->limit(1)
            ->get(['kode_rekening','nama_rekening']);

        return response()->json($result);
    }

}
