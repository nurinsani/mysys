<?php

namespace App\Http\Controllers;

use App\Exports\NeracaExport;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReportNeracaKpController extends ReportNeracaController
{
    public function index()
    {
        $title = 'Neraca';
        $menus = $this->getMenus();
        $data = $this->buildData(Auth::user()->unit);

        return view('kp.report_neraca.index', array_merge(compact('title', 'menus'), $data));
    }

    public function exportExcel()
    {
        $unit = Auth::user()->unit;
        $data = $this->buildData($unit);

        return Excel::download(new NeracaExport($data), 'Neraca_' . $unit . '.xlsx');
    }
}
