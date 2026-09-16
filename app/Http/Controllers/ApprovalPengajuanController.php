<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\ao;
use App\Models\Kelompok;
use App\Models\temp_akad_mus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApprovalPengajuanController extends BaseController
{
    public function index()
    {
        $menus = $this->getMenus();

        $title = 'Approval Pengajuan';

        return view('al.approval_pengajuan.index', compact('menus', 'title'));
    
    }
    
    public function get_pengajuan(Request $request)
    {
        $data = temp_akad_mus::where('status_app', 'PENDING')
            ->get();

        return response()->json([
            'data' => $data
        ]);
    }

    public function get_pengajuan_detail($no_anggota)
    {
        $title = 'Approval Pengajuan';

        $menus = $this->getMenus();

        $ao = ao::all();
        $kelompok = Kelompok::all();
        
        $detail = temp_akad_mus::with('anggota')
        ->where('no_anggota', $no_anggota)
        ->first();

        return view('al.approval_pengajuan.detail_anggota', compact('menus', 'title', 'detail', 'ao', 'kelompok'));
    }

    public function update_pengajuan(Request $request, $no_anggota)
    {
        temp_akad_mus::where('no_anggota', $no_anggota)->update([
            'nama' => $request->nama,
            'cif' => $request->cif,
            'plafond' => $request->plafond,
            'saldo_margin' => $request->saldo_margin,
            'updated_at' => now()
        ]);

        Anggota::where('no', $no_anggota)->update([
            'ibu_kandung' => $request->ibu_kandung,
            'updated_at' => now()
        ]);

        alert()->success('Berhasil!', 'Data berhasil diperbarui.');
        return redirect()->back()->with('success', 'Data pengajuan berhasil diperbarui.');
    }

    public function approvePengajuan($no_anggota)
    {
        $data = temp_akad_mus::where('no_anggota', $no_anggota)->firstOrFail();

        $data->status_app = 'APPROVED';
        $data->updated_at = now();
        $data->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil di-approve',
            'redirect' => url('/al/approval-pengajuan')
        ]);
    }

    public function approveCheckbox(Request $request)
    {
        $request->validate([
            'no_anggota' => 'required|array'
        ]);

        temp_akad_mus::whereIn('no_anggota', $request->no_anggota)
            ->update([
                'status_app' => 'APPROVED',
                'updated_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil di-approve'
        ]);
    }

    public function batalCheckbox(Request $request)
    {
        $request->validate([
            'no_anggota' => 'required|array'
        ]);

        temp_akad_mus::whereIn('no_anggota', $request->no_anggota)
            ->update([
                'status_app' => 'BATAL',
                'updated_at' => now()
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil di-reject'
        ]);
    }

    public function getKtp(Request $request)
    {
        $request->validate([
            'nik' => 'required|string'
        ]);

        $nik = $request->input('nik');

        try {
            $response = Http::timeout(10)->get("http://mobcoll.nurinsani.co.id/apimobcol/rmcKtp.php?ktp={$nik}");
        } catch (\Illuminate\Http\Client\ConnectionException|\GuzzleHttp\Exception\GuzzleException $e) {
            Log::error('Koneksi ke layanan cek KTP gagal: ' . $e->getMessage());
            return response()->json([
                'error' => 'Layanan cek KTP sedang tidak bisa diakses. Coba lagi nanti.'
            ], 503);
        }

        if (!$response->successful()) {
            return response()->json([
                'error' => 'Data tidak ditemukan'
            ], 404);
        }

        $data = $response->json();

        return response()->json($data);
    }

    

    public function ajukanKembali(Request $request)
    {
        $menus = $this->getMenus();

        $title = 'Ajukan Kembali';
        
        return view('al.approval_pengajuan.ajukan_kembali', compact('menus', 'title'));
        
    }

    public function getCifBatal(Request $request)
    {
        $search = $request->q;

        $data = DB::table('temp_akad_mus')
            ->where('status_app', 'batal')
            ->where(function ($query) use ($search) {
                $query->where('cif', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get();

        $result = [];

        foreach ($data as $row) {
            $result[] = [
                'id'   => $row->cif,
                'text' => $row->cif . ' - ' . $row->nama
            ];
        }

        return response()->json($result);
    }

    public function prosesAjukanKembali(Request $request)
    {
        $request->validate([
            'cif' => 'required'
        ]);

        DB::table('temp_akad_mus')
            ->where('cif', $request->cif)
            ->where('status_app', 'batal')
            ->update([
                'status_app' => 'PENDING',
                'updated_at' => now()
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan berhasil diajukan kembali'
        ]);
    }

    public function hapusPengajuan(Request $request)
    {
        $menus = $this->getMenus();

        $title = 'Hapus Pengajuan';
        
        return view('al.approval_pengajuan.hapus_pengajuan', compact('menus', 'title'));  
    }

    public function getCifHapus(Request $request)
    {
        $search = $request->q;

        $data = DB::table('temp_akad_mus')
            ->whereIn('status_app', ['BATAL', 'PENDING'])
            ->where(function ($query) use ($search) {
                $query->where('cif', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get();

        $result = [];

        foreach ($data as $row) {
            $result[] = [
                'id'   => $row->cif,
                'text' => $row->cif . ' - ' . $row->nama
            ];
        }

        return response()->json($result);
    }

    public function prosesHapusPengajuan(Request $request)
    {
        $request->validate([
            'cif' => 'required'
        ]);

        $deleted = DB::table('temp_akad_mus')
            ->where('cif', $request->cif)
            ->delete();

        if ($deleted == 0) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak ditemukan atau status bukan batal'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan berhasil dihapus'
        ]);
    }

    public function turunPlafond(Request $request)
    {
        $menus = $this->getMenus();

        $title = 'Turun Plafond';
        
        return view('al.approval_pengajuan.turun_plafond', compact('menus', 'title'));  
    }

    public function getCifTurunPlafond(Request $request)
    {
        $search = $request->q;

        $data = DB::table('temp_akad_mus')
            ->where('status_app', 'PENDING')
            ->where(function ($query) use ($search) {
                $query->where('cif', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%");
            })
            ->limit(10)
            ->get();

        $result = [];

        foreach ($data as $row) {
            $result[] = [
                'id'   => $row->cif,
                'text' => $row->cif . ' - ' . $row->nama
            ];
        }

        return response()->json($result);
    }

    public function prosesTurunPlafond(Request $request)
    {
        $request->validate([
            'cif'        => 'required',
            'harga_baru' => 'required|numeric|min:1'
        ]);

        $akad = DB::table('temp_akad_mus')
            ->where('cif', $request->cif)
            ->first();

        if (!$akad) {
            return response()->json([
                'status' => false,
                'message' => 'Data akad tidak ditemukan'
            ], 404);
        }

        $plafond = (int) $request->harga_baru;
        $tenor   = $akad->tenor;

        $param = DB::table('param_biaya')
            ->where('pla', $plafond)
            ->where('jw', $tenor)
            ->first();

        if (!$param) {
            return response()->json([
                'status' => false,
                'message' => 'Parameter biaya tidak ditemukan'
            ], 404);
        }

        $nilaiMargin = $plafond * ($param->margin / 100);
        $pokok  = round($plafond / $param->jw);
        $os     = $plafond + $nilaiMargin;
        $ijaroh = round($nilaiMargin / $param->jw);
        $angsuran = $pokok + $ijaroh;
        $bulat = $angsuran + $param->tab;

        DB::table('temp_akad_mus')
            ->where('cif', $request->cif)
            ->update([
                'bagi_hasil' => $nilaiMargin,
                'plafond' => $plafond,
                'saldo_margin' => $nilaiMargin,
                'pokok' => $pokok,
                'os' => $os,
                'angsuran' => $angsuran,
                'ijaroh' => $ijaroh,
                'bulat' => $bulat,
                'status_app' => 'PENDING',
                'updated_at' => now()
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Turun plafond berhasil diproses'
        ]);
    }



}
