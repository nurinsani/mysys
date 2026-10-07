<?php

namespace App\Http\Controllers;

use App\Exports\AnggotaExport;
use App\Http\Requests\Anggota\StoreAnggotaRequest;
use App\Http\Requests\Anggota\UpdateAnggotaRequest;
use App\Models\Anggota;
use App\Models\AnggotaDetail;
use App\Models\ao;
use App\Models\Kelompok;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class AnggotaController extends BaseController
{
    public function data()
    {
        $anggota = DB::table('anggota')->latest();

        return datatables()
            ->of($anggota)
            ->addIndexColumn()
            ->addColumn('aksi', function ($anggota) {
                return '
                <a href="' . route('anggota.edit', $anggota->no) . '" class="btn btn-sm btn-warning">Edit</a>
                ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function cari(Request $request)
    {
        $cari = $request->input('cari');

        $results = DB::table('anggota')
            ->select('cif', 'nama', 'no_hp')
            ->where('cif', 'like', '%' . $cari . '%')
            ->orWhere('nama', 'like', '%' . $cari . '%')
            ->limit(10)
            ->get();

        return response()->json($results);
    }

    public function index()
    {
        $title = 'Master Anggota';
        $ao = ao::all();
        $menus = $this->getMenus();

        return view('admin.master_anggota.index', compact('title', 'ao', 'menus'));
    }

    public function create()
    {
        $title = 'Input data Anggota';
        $ao = ao::all();
        $kelompok = Kelompok::all();
        $menus = $this->getMenus();

        return view('admin.master_anggota.create', compact('title', 'ao', 'menus', 'kelompok'));
    }

    public function getKelompokData(Request $request)
    {
        $kelompok = DB::table('kelompok')
            ->leftJoin('anggota', 'kelompok.cif', '=', 'anggota.cif')
            ->where('kelompok.code_kel', $request->code_kel)
            ->select(
                'kelompok.cao',
                'kelompok.cif',
                'kelompok.no_tlp',
                'anggota.nama as nama_ketua'
            )
            ->first();

        if ($kelompok) {
            return response()->json([
                'nama_ketua' => $kelompok->nama_ketua ?? '',
                'no_tlp' => $kelompok->no_tlp,
            ]);
        }

        return response()->json([]);
    }

    public function cariKtp(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
        ]);

        $nik = $request->input('nik');

        try {
            $response = Http::timeout(10)->get(config('services.mobcol.ktp_url'), ['ktp' => $nik]);
        } catch (\Illuminate\Http\Client\ConnectionException | \GuzzleHttp\Exception\GuzzleException $e) {
            Log::error('Koneksi ke layanan cek KTP gagal: ' . $e->getMessage());

            return response()->json([
                'error' => 'Layanan cek KTP sedang tidak bisa diakses. Coba lagi nanti.',
            ], 503);
        }

        if (! $response->successful()) {
            return response()->json([
                'error' => 'Data tidak ditemukan',
            ], 404);
        }

        $data = $response->json();

        return response()->json($data);
    }

    public function getKelompokByCao($cao)
    {
        $kelompok = Kelompok::where('cao', $cao)->get();

        return response()->json($kelompok);
    }

    public function store(StoreAnggotaRequest $request)
    {
        $unit = Auth::user()->unit;

        try {
            Log::info('Data yang diterima:', $request->all());

            $anggota = DB::transaction(function () use ($request, $unit) {
                $date = Carbon::now()->format('ymd');

                // Di-scope ke unit + hari ini, dan dikunci (lockForUpdate) supaya 2
                // input anggota di unit & hari yang sama nyaris bersamaan tidak
                // menghasilkan no_anggota yang sama (primary key collision).
                $lastAnggota = Anggota::where('unit', $unit)
                    ->whereDate('created_at', Carbon::today())
                    ->lockForUpdate()
                    ->latest()
                    ->first();

                $sequence = $lastAnggota ? intval(substr($lastAnggota->no, -3)) + 1 : 1;
                $sequenceFormatted = str_pad($sequence, 3, '0', STR_PAD_LEFT);

                $noAnggota = "{$unit}{$date}{$sequenceFormatted}";

                $anggota = Anggota::create([
                    'no' => $noAnggota,
                    'unit' => $unit,
                    'kode_kel' => strtoupper($request->kode_kel),
                    'norek' => $noAnggota,
                    'tgl_join' => Carbon::now(),
                    'cif' => strtoupper($request->cif),
                    'nama' => strtoupper($request->nama),
                    'deal_type' => '1',
                    'alamat' => strtoupper($request->alamat),
                    'desa' => strtoupper($request->desa),
                    'kecamatan' => strtoupper($request->kecamatan),
                    'kota' => strtoupper($request->kota),
                    'rtrw' => strtoupper($request->rtrw),
                    'no_hp' => strtoupper($request->no_hp),
                    'hp_pasangan' => strtoupper($request->hp_pasangan),
                    'kelamin' => strtoupper($request->kelamin),
                    'tgl_lahir' => $request->tgl_lahir,
                    'ktp' => strtoupper($request->ktp),
                    'kewarganegaraan' => strtoupper($request->kewarganegaraan),
                    'status_menikah' => strtoupper($request->status_menikah),
                    'agama' => strtoupper($request->agama),
                    'ibu_kandung' => strtoupper($request->ibu_kandung),
                    'npwp' => 0,
                    'source_income' => 1,
                    'pendidikan' => strtoupper($request->pendidikan),
                    'tempat_lahir' => strtoupper($request->tempat_lahir),
                    'id_expired' => 0,
                    'waris' => strtoupper($request->waris),
                    'cao' => strtoupper($request->cao),
                    'cao_promotor' => strtoupper($request->cao),
                    'userid' => Auth::id(),
                    'status' => 'ANGGOTA',
                    'pekerjaan_pasangan' => strtoupper($request->pekerjaan_pasangan),
                    'kode_pos' => strtoupper($request->kode_pos),
                ]);

                AnggotaDetail::create([
                    'no_anggota' => $noAnggota,
                    'alamat_domisili' => strtoupper($request->alamat_domisili ?? $request->alamat),
                    'rtrw_domisili' => strtoupper($request->rtrw_domisili ?? $request->rtrw),
                    'desa_domisili' => strtoupper($request->desa_domisili ?? $request->desa),
                    'kecamatan_domisili' => strtoupper($request->kecamatan_domisili ?? $request->kecamatan),
                    'kota_domisili' => strtoupper($request->kota_domisili ?? $request->kota),
                    'kode_pos_domisili' => strtoupper($request->kode_pos_domisili ?? $request->kode_pos),
                ]);

                return $anggota;
            });

            Log::info('Data anggota berhasil disimpan:', $anggota->toArray());

            alert()->success('Berhasil!', 'Data Berhasil Disimpan.');

            return redirect()->route('anggota.index');
        } catch (\Throwable $th) {
            Log::error('Error saat menyimpan data anggota:', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            alert()->error('Gagal!', 'Gagal saat menyimpan data.');

            return redirect()->back()->withInput()->with(['error' => 'Terjadi kesalahan: ' . $th->getMessage()]);
        }
    }

    public function edit(string $id)
    {
        $title = 'Edit data Anggota';
        $anggota = Anggota::where('no', $id)->firstOrFail();
        $anggota_detail = AnggotaDetail::where('no_anggota', $id)->first();
        $ao = ao::all();
        $kelompok = Kelompok::all();
        $menus = $this->getMenus();

        return view('admin.master_anggota.edit', compact('anggota', 'title', 'ao', 'kelompok', 'menus', 'anggota_detail'));
    }

    public function update(UpdateAnggotaRequest $request, string $id)
    {

        $unit = Auth::user()->unit;

        try {
            Log::info('Data yang diterima:', $request->all());

            $anggota = Anggota::where('no', $id)->firstOrFail();

            $anggota->update([
                'unit' => $unit,
                'kode_kel' => strtoupper($request->kode_kel),
                'cif' => strtoupper($request->cif),
                'nama' => strtoupper($request->nama),
                'deal_type' => '1',
                'alamat' => strtoupper($request->alamat),
                'desa' => strtoupper($request->desa),
                'kecamatan' => strtoupper($request->kecamatan),
                'kota' => strtoupper($request->kota),
                'rtrw' => strtoupper($request->rtrw),
                'no_hp' => strtoupper($request->no_hp),
                'hp_pasangan' => strtoupper($request->hp_pasangan),
                'kelamin' => strtoupper($request->kelamin),
                'tgl_lahir' => $request->tgl_lahir,
                'ktp' => strtoupper($request->ktp),
                'kewarganegaraan' => strtoupper($request->kewarganegaraan),
                'status_menikah' => strtoupper($request->status_menikah),
                'agama' => strtoupper($request->agama),
                'ibu_kandung' => strtoupper($request->ibu_kandung),
                'npwp' => 0,
                'source_income' => 1,
                'pendidikan' => strtoupper($request->pendidikan),
                'tempat_lahir' => strtoupper($request->tempat_lahir),
                'id_expired' => 0,
                'waris' => strtoupper($request->waris),
                'cao' => strtoupper($request->cao),
                'cao_promotor' => strtoupper($request->cao),
                'userid' => Auth::id(),
                'status' => 'ANGGOTA',
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

            Log::info('Data anggota berhasil diperbarui:', $anggota->toArray());

            alert()->success('Berhasil!', 'Data Berhasil Diperbarui.');

            return redirect()->route('anggota.index');
        } catch (\Throwable $th) {
            Log::error('Error saat memperbarui data anggota:', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            alert()->error('Gagal!', 'Gagal saat memperbarui data.');

            return redirect()->back()->withInput()->with(['error' => 'Terjadi kesalahan: ' . $th->getMessage()]);
        }
    }

    public function export()
    {
        return Excel::download(new AnggotaExport(Auth::user()->unit), 'anggota.xlsx');
    }
}
