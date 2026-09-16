<?php

namespace App\Http\Controllers;

use App\Exports\EkuitasExport;
use App\Models\ReportEkuitas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportEkuitasController extends BaseController
{
    public function index()
    {
        $title = 'Report Ekuitas';
        $menus = $this->getMenus();
        $report_ekuitas = ReportEkuitas::all();
        return view('admin.report_ekuitas.index', compact('title', 'menus', 'report_ekuitas'));
    }

    public function getData(Request $request)
    {
        $jenis = $request->jenis_pull;
        $bulan = $request->jenis_transaksi;
        $tahun = $request->tahun;
        $today = Carbon::today()->format('Y-m-d');
        $menus = $this->getMenus();

        $rekening_mapping = [
            'simpanan_pokok' => 3100000,
            'simpanan_wajib' => 3200000,
            'hibah' => 3600000,
            'cadangan' => 3801000,
            'shu' => 3902000,
        ];

        $data = new \stdClass();

        if ($jenis == '01') { // EOM
            foreach ($rekening_mapping as $key => $kode) {
                if ($kode) {
                    $result = DB::table('tabel_master_konsol')
                        ->select(
                            DB::raw('SUM(saldo_awal) as saldo_awal'),
                            DB::raw('SUM(saldo_akhir) as saldo_akhir')
                        )
                        ->whereMonth('tanggal_awal', $bulan)
                        ->whereYear('tanggal_awal', $tahun)
                        ->where('kode_rekening', $kode)
                        ->first();
                    
                    $data->{$key . '_awal'} = $result->saldo_awal ?? 0;
                    $data->{$key . '_akhir'} = $result->saldo_akhir ?? 0;
                } else {
                    $data->{$key . '_awal'} = 0;
                    $data->{$key . '_akhir'} = 0;
                }
            }
        } elseif ($jenis == '02') { // Current
            foreach ($rekening_mapping as $key => $kode) {
                if ($kode) {
                    $result = DB::table('tabel_master')
                        ->select(
                            DB::raw('SUM(saldo_awal) as saldo_awal'),
                            DB::raw('SUM(saldo_akhir) as saldo_akhir')
                        )
                        ->whereDate('tanggal_awal', $today)
                        ->where('kode_rekening', $kode)
                        ->first();
                    
                    $data->{$key . '_awal'} = $result->saldo_awal ?? 0;
                    $data->{$key . '_akhir'} = $result->saldo_akhir ?? 0;
                } else {
                    $data->{$key . '_awal'} = 0;
                    $data->{$key . '_akhir'} = 0;
                }
            }
        } else {
            foreach ($rekening_mapping as $key => $kode) {
                $data->{$key . '_awal'} = 0;
                $data->{$key . '_akhir'} = 0;
            }
        }

        foreach ($rekening_mapping as $key => $kode) {
            $data->{$key . '_penambahan'} = $data->{$key . '_akhir'} - $data->{$key . '_awal'};
        }

        $total_saldo_awal = $data->simpanan_pokok_awal + $data->simpanan_wajib_awal +
                            $data->hibah_awal + $data->cadangan_awal + $data->shu_awal;

        $total_penambahan = $data->simpanan_pokok_penambahan + $data->simpanan_wajib_penambahan +
                            $data->hibah_penambahan + $data->cadangan_penambahan + $data->shu_penambahan;

        $total_saldo_akhir = $data->simpanan_pokok_akhir + $data->simpanan_wajib_akhir +
                            $data->hibah_akhir + $data->cadangan_akhir + $data->shu_akhir;

        $tanggal_display = '';
        if ($jenis == '01' && $bulan && $tahun) {
            $tanggal_display = Carbon::parse("$tahun-$bulan-01")->endOfMonth()->format('d-M-y');
        } elseif ($jenis == '02') {
            $tanggal_display = Carbon::today()->format('d-M-y');
        }

        return view('admin.report_ekuitas.index', compact(
            'data',
            'jenis',
            'bulan',
            'tahun',
            'total_saldo_awal',
            'total_penambahan',
            'total_saldo_akhir',
            'tanggal_display',
            'menus'
        ));
    }

    public function exportExcel(Request $request)
    {
        $jenis = $request->jenis;
        $bulan = $request->bulan;
        $tahun = $request->tahun;
        $today = Carbon::today()->format('Y-m-d');

        $rekening_mapping = [
            'simpanan_pokok' => 3100000,
            'simpanan_wajib' => 3200000,
            'hibah' => 3600000,
            'cadangan' => 3801000,
            'shu' => 3902000,
        ];

        $data = new \stdClass();

        if ($jenis == '01') { // EOM
            foreach ($rekening_mapping as $key => $kode) {
                if ($kode) {
                    $result = DB::table('tabel_master_konsol')
                        ->select(
                            DB::raw('SUM(saldo_awal) as saldo_awal'),
                            DB::raw('SUM(saldo_akhir) as saldo_akhir')
                        )
                        ->whereMonth('tanggal_awal', $bulan)
                        ->whereYear('tanggal_awal', $tahun)
                        ->where('kode_rekening', $kode)
                        ->first();
                    
                    $data->{$key . '_awal'} = $result->saldo_awal ?? 0;
                    $data->{$key . '_akhir'} = $result->saldo_akhir ?? 0;
                } else {
                    $data->{$key . '_awal'} = 0;
                    $data->{$key . '_akhir'} = 0;
                }
            }
        } elseif ($jenis == '02') { // Current
            foreach ($rekening_mapping as $key => $kode) {
                if ($kode) {
                    $result = DB::table('tabel_master')
                        ->select(
                            DB::raw('SUM(saldo_awal) as saldo_awal'),
                            DB::raw('SUM(saldo_akhir) as saldo_akhir')
                        )
                        ->whereDate('tanggal_awal', $today)
                        ->where('kode_rekening', $kode)
                        ->first();
                    
                    $data->{$key . '_awal'} = $result->saldo_awal ?? 0;
                    $data->{$key . '_akhir'} = $result->saldo_akhir ?? 0;
                } else {
                    $data->{$key . '_awal'} = 0;
                    $data->{$key . '_akhir'} = 0;
                }
            }
        } else {
            foreach ($rekening_mapping as $key => $kode) {
                $data->{$key . '_awal'} = 0;
                $data->{$key . '_akhir'} = 0;
            }
        }

        foreach ($rekening_mapping as $key => $kode) {
            $data->{$key . '_penambahan'} = $data->{$key . '_akhir'} - $data->{$key . '_awal'};
        }

        $total_saldo_awal = $data->simpanan_pokok_awal + $data->simpanan_wajib_awal +
                            $data->hibah_awal + $data->cadangan_awal + $data->shu_awal;
        
        $total_penambahan = $data->simpanan_pokok_penambahan + $data->simpanan_wajib_penambahan + 
                            $data->hibah_penambahan + $data->cadangan_penambahan + $data->shu_penambahan;
        
        $total_saldo_akhir = $data->simpanan_pokok_akhir + $data->simpanan_wajib_akhir + 
                            $data->hibah_akhir + $data->cadangan_akhir + $data->shu_akhir;

        $tanggal_display = '';
        if ($jenis == '01' && $bulan && $tahun) {
            $tanggal_display = Carbon::parse("$tahun-$bulan-01")->endOfMonth()->format('d-M-y');
        } elseif ($jenis == '02') {
            $tanggal_display = Carbon::today()->format('d-M-y');
        }

        $filename = 'Laporan_Ekuitas_' . $tanggal_display . '.xlsx';

        return Excel::download(
            new EkuitasExport($data, $tanggal_display, $total_saldo_awal, $total_penambahan, $total_saldo_akhir),
            $filename
        );
    }
}
