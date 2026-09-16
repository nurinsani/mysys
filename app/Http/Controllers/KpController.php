<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class KpController extends BaseController
{
    public function index()
    {
    $menus = $this->getMenus();

        $pembiayaan = DB::table('pembiayaan')
            ->selectRaw('SUM(os - saldo_margin) as os, COUNT(cif) as noa')
            ->first();

        return view('kp.index', compact('menus', 'pembiayaan'));

    }
}
