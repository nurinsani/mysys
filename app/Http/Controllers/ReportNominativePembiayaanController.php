<?php

namespace App\Http\Controllers;

use App\Exports\PembiayaanExport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ReportNominativePembiayaanController extends BaseController
{
    public function index()
    {
        $title = 'Report Nominative Pembiayaan';
        $menus = $this->getMenus();

        return view('admin.report_nominative_pembiayaan.index', compact('menus', 'title'));
    }

    public function getData(Request $request)
    {
        $status = $request->input('status_nominative');
        $get_bulan = $request->input('bulan');
        $get_tahun = $request->input('tahun');

        $table = $status === 'eom' ? 'data_loan' : 'pembiayaan';

        $query = DB::table($table)
            ->select(
                'unit',
                DB::raw('COUNT(DISTINCT no_anggota) as total_noa'),
                DB::raw('SUM(os) as total_saldo')
            )
            ->where('unit', Auth::user()->unit)
            ->groupBy('unit');

        if ($status === 'current') {
            $today = Carbon::now()->format('Y-m-d');
            $query->whereDate('buss_date', $today)
                ->where('os', '>', 0);
        } elseif ($status === 'eom') {
            $bulan = match(strtolower($get_bulan)) {
                'januari' => '01',
                'februari' => '02',
                'maret' => '03',
                'april' => '04',
                'mei' => '05',
                'juni' => '06',
                'juli' => '07',
                'agustus' => '08',
                'september' => '09',
                'oktober' => '10',
                'november' => '11',
                'desember' => '12',
                default => null,
            };

            if (!$bulan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bulan tidak valid'
                ], 400);
            }

            $query->whereYear('buss_date', $get_tahun)
                ->whereMonth('buss_date', $bulan)
                ->groupBy('buss_date', 'unit');
        }


        $data = $query->get();
        
        Log::info('Query Result', [
            'count' => $data->count(),
            'data' => $data
        ]);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }


    public function export(Request $request)
    {
        $unit = Auth::user()->unit;
        $status = $request->input('status_nominative');
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        return Excel::download(
            new PembiayaanExport($status, $bulan, $tahun, $unit),
            "nominative_pembiayaan_{$unit}_{$bulan}_{$tahun}.xlsx"
        );

    }
}
