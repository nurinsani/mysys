<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\ao;
use App\Models\Kelompok;
use App\Models\pembiayaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemeliharaanKelompok extends BaseController
{
    public function data()
    {
        $kelompok = DB::table('kelompok')
        ->join('pembiayaan', 'kelompok.code_kel', '=', 'pembiayaan.code_kel')
        ->select(
            'kelompok.*',
            'pembiayaan.*',
        );

        return datatables()
            ->of($kelompok)
            ->addIndexColumn()
            ->addColumn('aksi', function($kelompok) {
                return '
                    <button onclick="editForm(`'. route('pemeliharaan-kelompok.update', $kelompok->code_kel) .'`)" class="btn btn-sm btn-primary">Edit</button>
                ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function index()
    {
        $title = 'Pemeliharaan Kelompok';
        $menus = $this->getMenus();
        $ao = ao::all();
        $anggota = Anggota::all();
        return view('admin.pemeliharaan_kelompok.index', compact('menus', 'title', 'ao', 'anggota'));
    }

    public function show($id)
    {
        $kelompok = Kelompok::where('code_kel', $id)->first();

        return response()->json($kelompok);
    }

    public function edit($id)
    {
        $kelompok = DB::table('kelompok')
        ->join('pembiayaan', 'kelompok.code_kel', '=', 'pembiayaan.code_kel')
        ->select(
            'kelompok.*',
            'pembiayaan.hari',
        )->where('kelompok.code_kel', $id)->first();

        return response()->json($kelompok);
    }

    public function update(Request $request, string $id)
    {
        $kelompok = Kelompok::where('code_kel', $id)->first();
        $kelompok->update($request->all());

        return response()->json('Data berhasil disimpan', 200);
    }

}
