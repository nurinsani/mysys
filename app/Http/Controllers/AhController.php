<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AhController extends BaseController
{
    public function index()
    {
        $menus = $this->getMenus();

        $pembiayaan = DB::table('pembiayaan')
            ->selectRaw('SUM(os - saldo_margin) as os, COUNT(cif) as noa')
            ->first();

        return view('ah.index', compact('menus', 'pembiayaan'));
    }
}
