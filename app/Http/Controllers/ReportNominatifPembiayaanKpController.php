<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\PembiayaanExport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;


class ReportNominatifPembiayaanKpController extends BaseController
{
     public function index()
    {
        $title = 'Report Nominative Pembiayaan';
           $menus = $this->getMenus();

        return view('kp.report_nominative_pembiayaan.index', compact('menus', 'title'));
    }

    public function getData(Request $request)
    {
        $status = $request->input('status_nominative');
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

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
            $bulanAngka = match(strtolower($bulan)) {
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

            if (!$bulanAngka) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bulan tidak valid'
                ], 400);
            }

            $startDate = Carbon::createFromDate($tahun, $bulanAngka, 1)->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::createFromDate($tahun, $bulanAngka, 1)->endOfMonth()->format('Y-m-d');

            $query->whereBetween('buss_date', [$startDate, $endDate]);

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
        $status = $request->input('status_nominative');
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        return Excel::download(new PembiayaanExport($status, $bulan, $tahun), 'pembiayaan.xlsx');
    }
}
