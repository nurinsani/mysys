<?php

namespace App\Http\Controllers;

use App\Models\ao;
use App\Models\Kelompok;
use App\Repositories\Contracts\AnggotaRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KelompokController extends BaseController
{
    public function __construct(protected AnggotaRepositoryInterface $anggotaRepository)
    {
    }

    public function data()
    {
        $kelompok = Kelompok::latest()->get();

        return datatables()
            ->of($kelompok)
            ->addIndexColumn()
            ->addColumn('aksi', function($kelompok) {
                return '
                    <button onclick="editForm(`'. route('kelompok.update', $kelompok->code_kel) .'`)" class="btn btn-sm btn-primary">Edit</button>
                    <button onclick="hapusData(`'. route('kelompok.destroy', $kelompok->code_kel) .'`)" class="btn btn-sm btn-danger">Hapus</button>
                ';
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }
    public function index()
    {
        $title = 'Master Kelompok';
        $menus = $this->getMenus();
        $ao = ao::all();
        return view('admin.master_kelompok.index', compact('menus', 'title', 'ao'));
    }

    public function getAnggotaByCif($cif)
    {
        $anggota = $this->anggotaRepository->findByCif($cif);

        if ($anggota) {
            return response()->json([
                'success' => true,
                'data' => $anggota
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Data tidak ditemukan'
        ], 404);
    }

    public static function generateNextCodeKelompok($unit = null)
    {
        $unit = $unit ?? (Auth::check() ? Auth::user()->unit : null) ?? '001';
        $allKelompoks = Kelompok::withTrashed()->where('code_kel', 'LIKE', $unit.'-%')->pluck('code_kel');
        $maxSeq = 0;
        foreach ($allKelompoks as $ck) {
            $parts = explode('-', $ck);
            if (isset($parts[1]) && is_numeric($parts[1])) {
                $num = intval($parts[1]);
                if ($num > $maxSeq) {
                    $maxSeq = $num;
                }
            }
        }
        $nextSeq = $maxSeq + 1;
        return $unit . '-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);
    }

    public function getNextCode(Request $request)
    {
        $unit = $request->code_unit ?? (Auth::check() ? Auth::user()->unit : null) ?? '001';
        $code = self::generateNextCodeKelompok($unit);
        return response()->json(['code_kel' => $code]);
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code_kel' => 'nullable|string|max:10|unique:kelompok,code_kel',
                'code_unit' => 'nullable|string|max:10',
                'nama_kel' => 'required|string|max:255',
                'alamat' => 'required',
                'cao' => 'required',
                'cif' => 'required',
                'no_tlp' => 'required|max:16',
            ]);

            $unit = $request->code_unit ?? (Auth::check() ? Auth::user()->unit : null) ?? '001';

            if ($request->filled('code_kel')) {
                $validated['code_kel'] = strtoupper($request->code_kel);
            } else {
                $validated['code_kel'] = self::generateNextCodeKelompok($unit);
            }

            $validated['code_unit'] = strtoupper($unit);
            $validated['nama_kel'] = strtoupper($validated['nama_kel']);
            $validated['alamat'] = strtoupper($validated['alamat']);
            $validated['cao'] = strtoupper($validated['cao']);
            $validated['cif'] = strtoupper($validated['cif']);
            $validated['no_tlp'] = strtoupper($validated['no_tlp']);

            Kelompok::create($validated);

            return response()->json(['message' => 'Data berhasil disimpan'], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validasi gagal: ' . implode(', ', \Illuminate\Support\Arr::flatten($e->errors())),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saat menyimpan data: ' . $e->getMessage());

            return response()->json(['message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }


    public function show($id)
    {
        $kelompok = Kelompok::where('code_kel', $id)->first();

        return response()->json($kelompok);
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        $kelompok = Kelompok::where('code_kel', $id)->first();
        $kelompok->update($request->all());

        return response()->json('Data berhasil disimpan', 200);
    }

    public function destroy($id)
    {
        try {
            $kelompok = Kelompok::where('code_kel', $id)->first();

            if (!$kelompok) {
                return response()->json(['message' => 'Data tidak ditemukan'], 404);
            }

            $kelompok->delete();

            return response()->json(['message' => 'Data berhasil dihapus.'], 204);
        } catch (\Exception $e) {
            Log::error('Error saat menghapus data: ' . $e->getMessage());

            return response()->json(['message' => 'Terjadi kesalahan'], 500);
        }
    }
    
}
