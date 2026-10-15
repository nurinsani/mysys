<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\Pembiayaan\AddPembiayaanRequest;
use App\Repositories\Contracts\KelompokRepositoryInterface;
use App\Models\Anggota;
use App\Models\AnggotaDetail;

class PembiayaanController extends BaseController
{
    public function __construct(
        protected KelompokRepositoryInterface $kelompokRepository
    ) {
    }

    public function index()
    {
        $title = 'Master Pembiayaan';
        $menus = $this->getMenus();
        return view('admin.master_pembiayaan.index', compact('title', 'menus'));
    }

    public function cariKelompok(Request $request)
    {
        $results = $this->kelompokRepository->search($request->cari, Auth::user()->unit, 10);

        return response()->json($results);
    }

    public function data(Request $request)
    {
        try {
            $kodeKelompok = $request->get('kode_kelompok');

            if (!$kodeKelompok) {
                $pending = DB::table('temp_akad_mus')
                    ->leftJoin('kelompok', 'temp_akad_mus.code_kel', '=', 'kelompok.code_kel')
                    ->where('temp_akad_mus.unit', Auth::user()->unit)
                    ->select(
                        'kelompok.nama_kel as nama_kelompok',
                        'temp_akad_mus.code_kel as kode_kel',
                        'temp_akad_mus.no_anggota as no_anggota',
                        'temp_akad_mus.unit as unit_anggota',
                        'temp_akad_mus.cao as anggota_cao',
                        'temp_akad_mus.cif as anggota_cif',
                        'temp_akad_mus.nama as nama_anggota',
                        'temp_akad_mus.suffix as suffix',
                        'temp_akad_mus.plafond as plafond',
                        'temp_akad_mus.os as os',
                        'temp_akad_mus.tenor as tenor'
                    )
                    ->latest('temp_akad_mus.created_at')
                    ->get();

                return response()->json([
                    'status' => 'success',
                    'data' => $pending
                ]);
            }

            $anggota = DB::table('anggota')
                ->leftJoin('kelompok', 'anggota.kode_kel', '=', 'kelompok.code_kel')
                ->leftJoin('pembiayaan', 'anggota.cif', '=', 'pembiayaan.cif')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('temp_akad_mus')
                        ->whereColumn('temp_akad_mus.no_anggota', 'anggota.no')
                        ->whereColumn('temp_akad_mus.cif', 'anggota.cif')
                        ->whereColumn('temp_akad_mus.unit', 'anggota.unit');
                })
                ->where('kelompok.code_kel', $kodeKelompok)
                ->select(
                    'kelompok.nama_kel as nama_kelompok',
                    'anggota.kode_kel as kode_kel',
                    'anggota.no as no_anggota',
                    'anggota.unit as unit_anggota',
                    'anggota.cif as anggota_cif',
                    'anggota.cao as anggota_cao',
                    'anggota.nama as nama_anggota',
                    'anggota.tgl_lahir as tanggal_lahir',
                    'pembiayaan.suffix as suffix',
                    DB::raw('COALESCE(pembiayaan.plafond, 0) as plafond'),
                    DB::raw('COALESCE(pembiayaan.os, 0) as os'),
                    DB::raw('COALESCE(pembiayaan.tenor, 0) as tenor')
                )
                ->latest('anggota.no')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $anggota
            ]);

        } catch (\Exception $e) {
            Log::error('Data Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong!'
            ], 500);
        }
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
            Log::error('Koneksi ke layanan cek KTP pembiayaan gagal: ' . $e->getMessage());

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

    public function edit($cif)
    {
        $title = 'Edit Pembiayaan';
        $menus = $this->getMenus();

        $pembiayaan = DB::table('anggota')
            ->leftJoin('kelompok', 'anggota.kode_kel', '=', 'kelompok.code_kel')
            ->leftJoin('pembiayaan', 'anggota.cif', '=', 'pembiayaan.cif')
            ->leftJoin('anggota_details', 'anggota.no', '=', 'anggota_details.no_anggota')
            ->where('anggota.cif', $cif)
            ->where('anggota.unit', Auth::user()->unit)
            ->select(
                'kelompok.nama_kel as nama_kelompok',
                'anggota.kode_kel',
                'anggota.no as no_anggota',
                'anggota.unit as unit_anggota',
                'anggota.cif as anggota_cif',
                'anggota.cao as anggota_cao',
                'anggota.nama as nama_anggota',
                'anggota.tgl_lahir as tanggal_lahir',
                'anggota.ktp as ktp',
                'anggota.alamat as alamat',
                'anggota.rtrw as rtrw',
                'anggota.desa as desa',
                'anggota.kecamatan as kecamatan',
                'anggota.kota as kota',
                'anggota.kode_pos as kode_pos',
                'anggota.no_hp as no_hp',
                'anggota.hp_pasangan as hp_pasangan',
                'anggota.status_menikah as status_menikah',
                'anggota.agama as agama',
                'anggota.pendidikan as pendidikan',
                'anggota.kewarganegaraan as kewarganegaraan',
                'anggota.waris as waris',
                'anggota.pekerjaan_pasangan as pekerjaan_pasangan',
                'anggota.ibu_kandung as ibu_kandung',
                'anggota.tempat_lahir as tempat_lahir',
                'anggota_details.alamat_domisili as alamat_domisili',
                'anggota_details.rtrw_domisili as rtrw_domisili',
                'anggota_details.desa_domisili as desa_domisili',
                'anggota_details.kecamatan_domisili as kecamatan_domisili',
                'anggota_details.kota_domisili as kota_domisili',
                'anggota_details.kode_pos_domisili as kode_pos_domisili',
                'pembiayaan.plafond',
                'pembiayaan.os',
                'pembiayaan.tenor',
                'pembiayaan.suffix',
                'pembiayaan.deal_produk'
            )
            ->first();

        if (!$pembiayaan) {
            return redirect()
                ->route('pembiayaan.index')
                ->with('error', 'Data pembiayaan tidak ditemukan');
        }

        $omzetMusyarakah = null;

        if ($pembiayaan->os > 0) {
            $omzetMusyarakah = DB::table('omzet')
                ->where('cif', $cif)
                ->min('nominal');
        }

        return view('admin.master_pembiayaan.edit', compact('title', 'menus', 'pembiayaan', 'omzetMusyarakah'));
    }


    public function addPembiayaan(AddPembiayaanRequest $request)
    {
        $validated = $request->validated();
        $unit = Auth::user()->unit;
        $userId = Auth::id();
        $jenisPembiayaan = (int) $validated['jenis_pembiayaan'];

        DB::beginTransaction();
        try {

            // Cek jika no_anggota / no_rek dipakai oleh CIF lain di temp_akad_mus
            $conflictRecord = DB::table('temp_akad_mus')
                ->where('cif', '!=', $validated['cif'])
                ->where('no_anggota', $validated['no_rek'])
                ->first();

            if ($conflictRecord) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => $conflictRecord->unit !== $unit
                        ? 'NIK / CIF already used in another unit'
                        : 'Record already exist'
                ], 400);
            }

            $existingRecord = DB::table('temp_akad_mus')
                ->where('cif', $validated['cif'])
                ->first();

            if ($existingRecord && $existingRecord->unit !== $unit) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'NIK / CIF already used in another unit'
                ], 400);
            }

            // Update data Anggota & AnggotaDetail jika ada perubahan
            $anggota = Anggota::where('cif', $validated['cif'])
                ->where('unit', $unit)
                ->first();

            if ($anggota) {
                $anggotaUpdate = [];
                if (array_key_exists('nama', $validated) && $validated['nama'] !== null) {
                    $anggotaUpdate['nama'] = strtoupper($validated['nama']);
                }
                if (array_key_exists('tempat_lahir', $validated) && $validated['tempat_lahir'] !== null) {
                    $anggotaUpdate['tempat_lahir'] = strtoupper($validated['tempat_lahir']);
                }
                if (array_key_exists('tgl_lahir', $validated) && $validated['tgl_lahir'] !== null) {
                    $anggotaUpdate['tgl_lahir'] = $validated['tgl_lahir'];
                }
                if (array_key_exists('alamat', $validated) && $validated['alamat'] !== null) {
                    $anggotaUpdate['alamat'] = strtoupper($validated['alamat']);
                }
                if (array_key_exists('rtrw', $validated) && $validated['rtrw'] !== null) {
                    $anggotaUpdate['rtrw'] = strtoupper($validated['rtrw']);
                }
                if (array_key_exists('desa', $validated) && $validated['desa'] !== null) {
                    $anggotaUpdate['desa'] = strtoupper($validated['desa']);
                }
                if (array_key_exists('kecamatan', $validated) && $validated['kecamatan'] !== null) {
                    $anggotaUpdate['kecamatan'] = strtoupper($validated['kecamatan']);
                }
                if (array_key_exists('kota', $validated) && $validated['kota'] !== null) {
                    $anggotaUpdate['kota'] = strtoupper($validated['kota']);
                }
                if (array_key_exists('kode_pos', $validated) && $validated['kode_pos'] !== null) {
                    $anggotaUpdate['kode_pos'] = strtoupper($validated['kode_pos']);
                }
                if (array_key_exists('status_menikah', $validated) && $validated['status_menikah'] !== null) {
                    $anggotaUpdate['status_menikah'] = strtoupper($validated['status_menikah']);
                }
                if (array_key_exists('agama', $validated) && $validated['agama'] !== null) {
                    $anggotaUpdate['agama'] = strtoupper($validated['agama']);
                }
                if (array_key_exists('pendidikan', $validated) && $validated['pendidikan'] !== null) {
                    $anggotaUpdate['pendidikan'] = strtoupper($validated['pendidikan']);
                }
                if (array_key_exists('kewarganegaraan', $validated) && $validated['kewarganegaraan'] !== null) {
                    $anggotaUpdate['kewarganegaraan'] = strtoupper($validated['kewarganegaraan']);
                }
                if (array_key_exists('waris', $validated) && $validated['waris'] !== null) {
                    $anggotaUpdate['waris'] = strtoupper($validated['waris']);
                }
                if (array_key_exists('pekerjaan_pasangan', $validated) && $validated['pekerjaan_pasangan'] !== null) {
                    $anggotaUpdate['pekerjaan_pasangan'] = strtoupper($validated['pekerjaan_pasangan']);
                }
                if (array_key_exists('no_hp', $validated) && $validated['no_hp'] !== null) {
                    $anggotaUpdate['no_hp'] = strtoupper($validated['no_hp']);
                }
                if (array_key_exists('hp_pasangan', $validated) && $validated['hp_pasangan'] !== null) {
                    $anggotaUpdate['hp_pasangan'] = strtoupper($validated['hp_pasangan']);
                }

                if (!empty($anggotaUpdate)) {
                    $anggota->update($anggotaUpdate);
                }

                $detailUpdate = [];
                if (array_key_exists('alamat_domisili', $validated)) {
                    $detailUpdate['alamat_domisili'] = strtoupper($validated['alamat_domisili'] ?? '');
                }
                if (array_key_exists('rtrw_domisili', $validated)) {
                    $detailUpdate['rtrw_domisili'] = strtoupper($validated['rtrw_domisili'] ?? '');
                }
                if (array_key_exists('desa_domisili', $validated)) {
                    $detailUpdate['desa_domisili'] = strtoupper($validated['desa_domisili'] ?? '');
                }
                if (array_key_exists('kecamatan_domisili', $validated)) {
                    $detailUpdate['kecamatan_domisili'] = strtoupper($validated['kecamatan_domisili'] ?? '');
                }
                if (array_key_exists('kota_domisili', $validated)) {
                    $detailUpdate['kota_domisili'] = strtoupper($validated['kota_domisili'] ?? '');
                }
                if (array_key_exists('kode_pos_domisili', $validated)) {
                    $detailUpdate['kode_pos_domisili'] = strtoupper($validated['kode_pos_domisili'] ?? '');
                }

                if (!empty($detailUpdate)) {
                    AnggotaDetail::updateOrCreate(
                        ['no_anggota' => $anggota->no],
                        $detailUpdate
                    );
                }
            }

            $lastSuffix = DB::table('temp_akad_mus')
                ->where('cif', $validated['cif'])
                ->max('suffix');

            $suffix = $lastSuffix ? ((int)$lastSuffix + 1) : 1;

            $param = DB::table('param_biaya')
                ->where('pla', (int) $validated['disetujui'])
                ->where('jw', (int) $validated['tenor'])
                ->first();

            if (!$param) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Approved amount & tenor not found in param biaya'
                ], 400);
            }

            $persenMargin = $param->margin / 100;
            $saldoMargin = $param->pla * $persenMargin;
            $outStanding = $param->pla + $saldoMargin;
            $pokok = $param->pla / $validated['tenor'];
            $ijaroh = $saldoMargin / $validated['tenor'];
            $angsuran = $pokok + $ijaroh;
            $bulat = $param->tab + $angsuran;

            // ================================
            // JIKA AKAD MUSYARAKAH
            // ================================
            $persenMarginMusyarokah = null;
            if ($jenisPembiayaan === 2) {

                if (empty($validated['omzet'])) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Omzet wajib diisi untuk pembiayaan Musyarakah'
                    ], 400);
                }

                $omzet = (float) $validated['omzet'];

                if ($omzet <= 0) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Omzet tidak valid'
                    ], 400);
                }

                $isPembiayaanLanjutan = DB::table('pembiayaan')
                    ->where('cif', $validated['cif'])
                    ->where('os', '>', 0)
                    ->exists();

                $basisOmzet = $isPembiayaanLanjutan ? $omzet : ($omzet / 2);

                if ($basisOmzet <= 0) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Basis omzet tidak valid'
                    ], 400);
                }

                $persenMarginMusyarokah = round(($ijaroh / $basisOmzet) * 100, 2);

                if (!$isPembiayaanLanjutan) {
                    DB::table('omzet')->updateOrInsert(
                        ['cif' => $validated['cif'], 'tanggal' => now()->toDateString()],
                        [
                            'nominal' => $omzet,
                            'updated_at' => now(),
                        ]
                    );
                }
            }


            $wakalahDate = Carbon::parse($validated['tgl_wakalah']);
            if ($wakalahDate->isFriday()) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Jumat bukan hari pengumpulan'
                ], 400);
            }

            $dayOfWeek = $wakalahDate->locale('id')->isoFormat('dddd');

            $age = Carbon::parse($validated['tgl_lahir'])->age;
            $statusUsia = $age > 50 ? 'DEVIASI' : 'NO';

            $tanggalLibur = DB::table('param_tgl')->pluck('param_tgl')->toArray();

            $tglMurab = $wakalahDate->copy()->addDays(7);
            $current  = $tglMurab->copy()->addDays(7);

            $jadwal = [];

            for ($i = 0; $i < $validated['tenor']; $i++) {

                $date = $current->copy();

                while (in_array($date->format('Y-m-d'), $tanggalLibur)) {
                    $date->addDays(7);
                }

                $jadwal[] = $date->format('Y-m-d H:i:s');
                $current->addDays(7);
            }

            $nextPayment     = $jadwal[0];
            $maturityPayment = end($jadwal);

            $akadData = [
                'buss_date' => Carbon::parse($validated['param_tanggal']),
                'code_kel' => $validated['kode_kel'],
                'no_anggota' => $validated['no_rek'],
                'cif' => $validated['cif'],
                'nama' => strtoupper($validated['nama']),
                'deal_type' => '1',
                'bagi_hasil'     => $saldoMargin,
                'tenor' => $validated['tenor'],
                'plafond' => $validated['disetujui'],
                'os' => $outStanding,
                'saldo_margin' => $jenisPembiayaan === 2 ? '0' : $saldoMargin,
                'angsuran' => $angsuran,
                'pokok' => $pokok,
                'ijaroh' => $ijaroh,
                'bulat' => $bulat,
                'run_tenor' => 0,
                'ke' => 1,
                'usaha' => $validated['bidang_usaha'],
                'nama_usaha' => $validated['keterangan_usaha'],
                'unit' => $unit,
                'tgl_wakalah' => $wakalahDate,
                'tgl_akad' => Carbon::parse($validated['tgl_akad']),
                'tgl_murab' => $tglMurab,
                'next_schedule' => $nextPayment,
                'maturity_date' => $maturityPayment,
                'last_payment' => null,
                'hari' => $dayOfWeek,
                'cao' => strtoupper($validated['cao']),
                'userid' => $userId,
                'status' => 'ANGGOTA',
                'status_usia' => $statusUsia,
                'status_app' => 'APPROVE',
                'gol' => 1,
                'deal_produk' => $jenisPembiayaan === 2 ? '3' : $jenisPembiayaan,
                'persen_margin' => $jenisPembiayaan === 2 ? $persenMarginMusyarokah : $persenMargin,
                'updated_at' => now(),
            ];

            if ($existingRecord) {
                DB::table('temp_akad_mus')
                    ->where('cif', $validated['cif'])
                    ->update($akadData);
            } else {
                $akadData['suffix'] = (string) $suffix;
                $akadData['created_at'] = now();
                DB::table('temp_akad_mus')->insert($akadData);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => $existingRecord ? 'Pembiayaan successfully updated.' : 'Pembiayaan successfully added.'
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Add/Update Pembiayaan Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan pembiayaan. Silakan coba lagi atau hubungi admin.'
            ], 500);
        }
    }

}
