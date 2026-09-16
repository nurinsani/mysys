<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\AnggotaDetail;
use App\Models\ao;
use App\Models\Kelompok;
use App\Models\pembiayaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ViewDataController extends BaseController
{
    public function data()
    {
        $anggota = DB::table('anggota')
        ->join('pembiayaan', 'anggota.no', '=', 'pembiayaan.no_anggota')
        ->select(
            'anggota.*',
            'anggota.kode_kel as kode_kel_anggota',
            'anggota.nama as nama_anggota',
            'anggota.cif as cif_anggota',
            'pembiayaan.tgl_wakalah',
            'pembiayaan.maturity_date',
            'pembiayaan.nama_usaha',
            'pembiayaan.hari',
            'pembiayaan.plafond as plafond_pembiayaan',
            'pembiayaan.os as os_pembiayaan',
            'pembiayaan.saldo_margin as saldo_margin_pembiayaan',
        );

        return datatables()
            ->of($anggota)
            ->addIndexColumn()
            ->addColumn('aksi', function($anggota) {
                return '
                <a href="'. route('view-data.edit', $anggota->no) .'" class="btn btn-sm btn-warning">Edit</a>
                ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function index()
    {
        $title = 'View Data';
        $ao = ao::all();
        $menus = $this->getMenus();
        return view('admin.view_data.index', compact('title', 'ao', 'menus'));
    }

    public function edit(string $id)
    {
        $title = 'Edit data Anggota';
        $anggota = DB::table('anggota')
        ->join('pembiayaan', 'anggota.no', '=', 'pembiayaan.no_anggota')
        ->select(
            'anggota.*',
            'pembiayaan.*',
        )
        ->where('anggota.no', $id)->firstOrFail();
        $anggota_detail = AnggotaDetail::where('no_anggota', $id)->first();
        $ao = ao::all();
        $kelompok = Kelompok::all();
        $menus = $this->getMenus();
        return view('admin.view_data.edit', compact('anggota','title','ao', 'kelompok', 'menus', 'anggota_detail'));
    }

    public function update(Request $request, string $id)
    {

        $unit = Auth::user()->unit;

        try {
            Log::info('Data yang diterima:', $request->all());
    
            $anggota = Anggota::where('no', $id)->first();
    
            $anggota->update([
                'cao' => $request->cao,
                'kode_kel' => strtoupper($request->kode_kel),
                'alamat' => strtoupper($request->alamat),
                'desa' => strtoupper($request->desa),
                'kecamatan' => strtoupper($request->kecamatan),
                'kota' => strtoupper($request->kota),
                'rtrw' => strtoupper($request->rtrw),
                'no_hp' => strtoupper($request->no_hp),
                'hp_pasangan' => strtoupper($request->hp_pasangan),
                'status_menikah' => strtoupper($request->status_menikah),
                'waris' => strtoupper($request->waris),
                'pekerjaan_pasangan' => strtoupper($request->pekerjaan_pasangan),
                'kode_pos' => strtoupper($request->kode_pos),
            ]);

            $anggotaDetail = AnggotaDetail::where('no_anggota', $id)->first();
            if ($anggotaDetail) {
                $anggotaDetail->update([
                    'alamat_domisili' => strtoupper($request->alamat_domisili ?? $request->alamat),
                    'rtrw_domisili' => strtoupper($request->rtrw_domisili ?? $request->rtrw),
                    'desa_domisili' => strtoupper($request->desa_domisili ?? $request->desa),
                    'kecamatan_domisili' => strtoupper($request->kecamatan_domisili ?? $request->kecamatan),
                    'kota_domisili' => strtoupper($request->kota_domisili ?? $request->kota),
                    'kode_pos_domisili' => strtoupper($request->kode_pos_domisili ?? $request->kode_pos),
                ]);
            } else {
                AnggotaDetail::create([
                    'no_anggota' => $id,
                    'alamat_domisili' => strtoupper($request->alamat_domisili ?? $request->alamat),
                    'rtrw_domisili' => strtoupper($request->rtrw_domisili ?? $request->rtrw),
                    'desa_domisili' => strtoupper($request->desa_domisili ?? $request->desa),
                    'kecamatan_domisili' => strtoupper($request->kecamatan_domisili ?? $request->kecamatan),
                    'kota_domisili' => strtoupper($request->kota_domisili ?? $request->kota),
                    'kode_pos_domisili' => strtoupper($request->kode_pos_domisili ?? $request->kode_pos),
                ]);
            }

            $anggotaPembiayaan = pembiayaan::where('no_anggota', $id)->first();
            if ($anggotaPembiayaan) {
                $anggotaPembiayaan->update([
                    'cao' => $anggota->cao,
                    'code_kel' => $anggota->kode_kel,
                    'tgl_wakalah' => ($request->tgl_wakalah ?? ''),
                    'maturity_date' => ($request->maturity_date ?? ''),
                    'nama_usaha' => strtoupper($request->nama_usaha ?? ''),
                    'hari' => $request->hari,
                ]);
            }
    
            Log::info('Data anggota berhasil diperbarui:', $anggota->toArray());
        
            alert()->success('Berhasil!', 'Data Berhasil Diperbarui.');
            return redirect()->route('view-data.index');
    
        } catch (\Throwable $th) {
            Log::error('Error saat memperbarui data anggota:', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);

            alert()->error('Gagal!', 'Gagal saat memperbarui data.');
            return redirect()->back()->withInput()->with(['error' => 'Terjadi kesalahan: ' . $th->getMessage()]);
        }
    }
}
