<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PostingJurnalController extends BaseController
{
    public function index()
    {
        $title = 'Posting Jurnal';
        $menus = $this->getMenus();
        $data = $this->buildData(Auth::user()->unit, Auth::user()->param_tanggal);

        return view('admin.posting_jurnal.index', array_merge(compact('title', 'menus'), $data));
    }

    protected function buildData(string $unit, string $tglSystem): array
    {
        $rows = DB::table('tabel_transaksi')
            ->leftJoin('coa', 'coa.kode_rek', '=', 'tabel_transaksi.kode_rekening')
            ->where('tabel_transaksi.unit', $unit)
            ->whereBetween('tabel_transaksi.tanggal_transaksi', ["{$tglSystem} 00:00:00", "{$tglSystem} 23:59:59"])
            ->where('tabel_transaksi.keterangan_posting', '')
            ->select(
                'tabel_transaksi.kode_rekening',
                'coa.nama_rek',
                DB::raw('SUM(tabel_transaksi.debet) as debet'),
                DB::raw('SUM(tabel_transaksi.kredit) as kredit')
            )
            ->groupBy('tabel_transaksi.kode_rekening', 'coa.nama_rek')
            ->orderBy('tabel_transaksi.kode_rekening')
            ->get();

        $total = DB::table('tabel_transaksi')
            ->where('unit', $unit)
            ->whereBetween('tanggal_transaksi', ["{$tglSystem} 00:00:00", "{$tglSystem} 23:59:59"])
            ->where('keterangan_posting', '')
            ->selectRaw('SUM(debet) as tot_debet, SUM(kredit) as tot_kredit')
            ->first();

        return compact('rows', 'total', 'tglSystem');
    }

    public function posting()
    {
        $this->jalankanPosting(Auth::user()->unit, Auth::user()->param_tanggal);

        return redirect()->route('posting-jurnal.index')->with('success', 'Proses Posting Selesai');
    }

    protected function jalankanPosting(string $unit, string $tglSystem): void
    {
        DB::transaction(function () use ($unit, $tglSystem) {
            $mutasi = DB::table('tabel_transaksi')
                ->where('unit', $unit)
                ->whereBetween('tanggal_transaksi', ["{$tglSystem} 00:00:00", "{$tglSystem} 23:59:59"])
                ->where('keterangan_posting', '')
                ->select('kode_rekening', DB::raw('SUM(debet) as debet'), DB::raw('SUM(kredit) as kredit'))
                ->groupBy('kode_rekening')
                ->get();

            foreach ($mutasi as $row) {
                if ($row->debet > 0) {
                    DB::table('tabel_master')
                        ->where('unit', $unit)
                        ->where('kode_rekening', $row->kode_rekening)
                        ->increment('mut_debet', $row->debet);
                }

                if ($row->kredit > 0) {
                    DB::table('tabel_master')
                        ->where('unit', $unit)
                        ->where('kode_rekening', $row->kode_rekening)
                        ->increment('mut_kredit', $row->kredit);
                }
            }

            $akun = DB::table('tabel_master')
                ->where('unit', $unit)
                ->select('kode_rekening', 'normal', 'awal_debet', 'awal_kredit', 'mut_debet', 'mut_kredit')
                ->get();

            foreach ($akun as $row) {
                if ($row->normal === 'debet') {
                    $sisa = ($row->awal_debet + $row->mut_debet) - $row->mut_kredit;
                    $sisaDebet = $sisa < 0 ? 0 : $sisa;
                    $sisaKredit = $sisa < 0 ? abs($sisa) : 0;
                } elseif ($row->normal === 'kredit') {
                    $sisa = ($row->awal_kredit - $row->mut_debet) + $row->mut_kredit;
                    $sisaDebet = $sisa < 0 ? abs($sisa) : 0;
                    $sisaKredit = $sisa < 0 ? 0 : $sisa;
                } else {
                    continue;
                }

                DB::table('tabel_master')
                    ->where('unit', $unit)
                    ->where('kode_rekening', $row->kode_rekening)
                    ->update(['sisa_debet' => $sisaDebet, 'sisa_kredit' => $sisaKredit]);
            }

            DB::table('tabel_transaksi')
                ->where('unit', $unit)
                ->whereBetween('tanggal_transaksi', ["{$tglSystem} 00:00:00", "{$tglSystem} 23:59:59"])
                ->where('keterangan_posting', '')
                ->update(['tanggal_posting' => now()->format('y-m-d'), 'keterangan_posting' => 'Post']);
        });
    }
}
