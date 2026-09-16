<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HapusBukuController extends BaseController
{
    public function index()
    {
        $menus = $this->getMenus();
        $title = 'Hapus Buku';

        return view("admin.hapus_buku.index", compact("menus", "title"));
    }

    public function searchCif(Request $request)
    {
        try {
            $term = $request->input('term');
            $unit = $request->input('unit');

            $data = DB::table('pembiayaan')
                ->where('pembiayaan.unit', $unit)
                ->where(function ($query) use ($term) {
                    $query->where('pembiayaan.cif', 'LIKE', "%{$term}%")
                        ->orWhere('pembiayaan.nama', 'LIKE', "%{$term}%");
                })
                ->select(
                    'pembiayaan.cif',
                    'pembiayaan.no_anggota',
                    'pembiayaan.nama',
                    'pembiayaan.plafond',
                    'pembiayaan.saldo_margin',
                    DB::raw('CAST(pembiayaan.run_tenor AS INTEGER) as run_tenor')
                )
                ->get();

            if ($data->isNotEmpty()) {
                $data = $data->map(function ($item) {
                    $simpananTotals = DB::table('simpanan')
                        ->where('cif', $item->cif)
                        ->selectRaw('COALESCE(SUM(kredit),0) as total_kredit, COALESCE(SUM(debet),0) as total_debet')
                        ->first();

                    $totalKredit = $simpananTotals->total_kredit ?? 0;
                    $totalDebet = $simpananTotals->total_debet ?? 0;
                    $item->simpanan = $totalKredit - $totalDebet;

                    $item->label = "{$item->cif} - {$item->nama}";
                    $item->value = $item->cif;
                    return $item;
                });
            }

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error searching CIF: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addTransaction(Request $request)
    {
        try {
            $validated = $request->validate([
                'nomor_bukti' => 'required|string',
                'tanggal' => 'required|date',
                'cif' => 'required|string',
                'pokok' => 'required|numeric',
                'margin' => 'required|numeric',
                'minggu_ke' => 'required|integer',
                'simpanan' => 'required|numeric',
                'jenis_wo' => 'required|in:NPF,Meninggal Dunia',
                'no_anggota' => 'required|string',
                'userUnit' => 'required|string',
                'userId' => 'required|string',
                'userDate' => 'required|date',
                'nama' => 'required|string'
            ]);

            $pembiayaan = DB::table('pembiayaan')
                ->where('cif', $validated['cif'])
                ->first();

            if (!$pembiayaan) {
                throw new \Exception('Data pembiayaan tidak ditemukan');
            }

            $finalPokok = $validated['pokok'];
            $runTenor = $validated['minggu_ke'];
            $administrativeAmount = 0;

            if ($validated['jenis_wo'] === 'NPF') {
                if ((int) $pembiayaan->gol <= 3) {
                    throw new \Exception('Golongan must be greater than 3 for NPF type');
                }
                $finalPokok = $validated['pokok'] - $validated['simpanan'] + $validated['margin'];
                $administrativeAmount = $validated['pokok'] - $validated['simpanan'];
            } elseif ($validated['jenis_wo'] === 'Meninggal Dunia') {
                if ($runTenor >= 13) {
                    $finalPokok = $validated['pokok'] + $validated['margin'];
                    $administrativeAmount = $validated['pokok'];
                } else {
                    $finalPokok = $validated['pokok'] - $validated['simpanan'] + $validated['margin'];
                    $administrativeAmount = $validated['pokok'] - $validated['simpanan'];
                }
            }

            $formattedDate = date('Y-m-d H:i:s', strtotime($validated['userDate']));

            DB::beginTransaction();
            try {
                $transactionRecords = [
                    [
                        'unit' => $validated['userUnit'],
                        'kode_transaksi' => $validated['nomor_bukti'],
                        'kode_rekening' => '1423000',
                        'tanggal_transaksi' => $formattedDate,
                        'jenis_transaksi' => 'bukti SYSTEM',
                        'keterangan_transaksi' => "PMYD-PYD Murabahah Mingguan AN {$validated['nama']}",
                        'debet' => $validated['margin'],
                        'kredit' => 0,
                        'tanggal_posting' => $validated['tanggal'],
                        'keterangan_posting' => "PMYD-PYD Murabahah Mingguan AN {$validated['nama']}",
                        'id_admin' => $validated['userId'],
                        'ip_address' => request()->ip(),
                    ],
                    [
                        'unit' => $validated['userUnit'],
                        'kode_transaksi' => $validated['nomor_bukti'],
                        'kode_rekening' => '1413000',
                        'tanggal_transaksi' => $formattedDate,
                        'jenis_transaksi' => 'bukti SYSTEM',
                        'keterangan_transaksi' => "Piutang Murabahah Mingguan AN {$validated['nama']}",
                        'debet' => 0,
                        'kredit' => $validated['margin'],
                        'tanggal_posting' => $validated['tanggal'],
                        'keterangan_posting' => "Piutang Murabahah Mingguan AN {$validated['nama']}",
                        'id_admin' => $validated['userId'],
                        'ip_address' => request()->ip(),
                    ],
                    [
                        'unit' => $validated['userUnit'],
                        'kode_transaksi' => $validated['nomor_bukti'],
                        'kode_rekening' => '1512000',
                        'tanggal_transaksi' => $formattedDate,
                        'jenis_transaksi' => 'bukti SYSTEM',
                        'keterangan_transaksi' => "PPA Umum-PYD Piutang Murabahah AN {$validated['nama']}",
                        'debet' => $administrativeAmount,
                        'kredit' => 0,
                        'tanggal_posting' => $validated['tanggal'],
                        'keterangan_posting' => "PPA Umum-PYD Piutang Murabahah AN {$validated['nama']}",
                        'id_admin' => $validated['userId'],
                        'ip_address' => request()->ip(),
                    ],
                    [
                        'unit' => $validated['userUnit'],
                        'kode_transaksi' => $validated['nomor_bukti'],
                        'kode_rekening' => '2101000',
                        'tanggal_transaksi' => $formattedDate,
                        'jenis_transaksi' => 'bukti SYSTEM',
                        'keterangan_transaksi' => "Simpanan Wadiah Kelompok AN {$validated['nama']}",
                        'debet' => $validated['simpanan'],
                        'kredit' => 0,
                        'tanggal_posting' => $validated['tanggal'],
                        'keterangan_posting' => "Simpanan Wadiah Kelompok AN {$validated['nama']}",
                        'id_admin' => $validated['userId'],
                        'ip_address' => request()->ip(),
                    ],
                    [
                        'unit' => $validated['userUnit'],
                        'kode_transaksi' => $validated['nomor_bukti'],
                        'kode_rekening' => '1413000',
                        'tanggal_transaksi' => $formattedDate,
                        'jenis_transaksi' => 'bukti SYSTEM',
                        'keterangan_transaksi' => "Piutang Murabahah Mingguan AN {$validated['nama']}",
                        'debet' => 0,
                        'kredit' => $validated['pokok'],
                        'tanggal_posting' => $validated['tanggal'],
                        'keterangan_posting' => "Piutang Murabahah Mingguan AN {$validated['nama']}",
                        'id_admin' => $validated['userId'],
                        'ip_address' => request()->ip(),
                    ],
                    [
                        'unit' => $validated['userUnit'],
                        'kode_transaksi' => $validated['nomor_bukti'],
                        'kode_rekening' => '9141000',
                        'tanggal_transaksi' => $formattedDate,
                        'jenis_transaksi' => 'bukti SYSTEM',
                        'keterangan_transaksi' => "Rekening Administratif - Piutang Murabahah AN {$validated['nama']}",
                        'debet' => $administrativeAmount,
                        'kredit' => 0,
                        'tanggal_posting' => $validated['tanggal'],
                        'keterangan_posting' => "Rekening Administratif - Piutang Murabahah AN {$validated['nama']}",
                        'id_admin' => $validated['userId'],
                        'ip_address' => request()->ip(),
                    ],
                    [
                        'unit' => $validated['userUnit'],
                        'kode_transaksi' => $validated['nomor_bukti'],
                        'kode_rekening' => '9910000',
                        'tanggal_transaksi' => $formattedDate,
                        'jenis_transaksi' => 'bukti SYSTEM',
                        'keterangan_transaksi' => "Rekening Administratif - Rekening Lawan AN {$validated['nama']}",
                        'debet' => 0,
                        'kredit' => $administrativeAmount,
                        'tanggal_posting' => $validated['tanggal'],
                        'keterangan_posting' => "Rekening Administratif - Rekening Lawan AN {$validated['nama']}",
                        'id_admin' => $validated['userId'],
                        'ip_address' => request()->ip(),
                    ]
                ];

                DB::table('tabel_transaksi')->insert($transactionRecords);
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Transaction added successfully'
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteTransaction(Request $request)
    {
        try {
            $nomor_bukti = $request->input('nomor_bukti');

            DB::beginTransaction();
            try {
                DB::table('tabel_transaksi')
                    ->where('kode_transaksi', $nomor_bukti)
                    ->delete();

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Transaction deleted successfully'
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    public function processAll(Request $request)
    {
        try {
            $records = $request->input('records');
            if (empty($records)) {
                throw new \Exception('No records to process');
            }

            DB::beginTransaction();
            try {
                foreach ($records as $record) {
                    $pembiayaan = DB::table('pembiayaan')
                        ->where('cif', $record['cif'])
                        ->first();

                    if (!$pembiayaan) {
                        throw new \Exception("Pembiayaan not found for CIF: {$record['cif']}");
                    }

                    $totalAmount = $record['pokok'] + $record['margin'];
                    $formattedDate = date('Y-m-d H:i:s', strtotime($record['userDate']));

                    DB::table('simpanan')->insert([
                        'reff' => generate_reff($record['userUnit']),
                        'buss_date' => $formattedDate,
                        'norek' => $record['no_anggota'],
                        'unit' => $record['userUnit'],
                        'cif' => $record['cif'],
                        'code_kel' => $pembiayaan->code_kel,
                        'debet' => $totalAmount,
                        'kredit' => 0,
                        'type' => 'WO',
                        'ket' => "Hapus buku {$record['jenis_wo']} AN {$record['nama']}",
                        'cao' => $pembiayaan->cao,
                        'tgl_input' => $record['tanggal'],
                        'kode_transaksi' => $record['nomor_bukti']
                    ]);

                    DB::table('rek_loan')->insert([
                        'tgl_realisasi' => $formattedDate,
                        'unit' => $record['userUnit'],
                        'no_anggota' => $record['no_anggota'],
                        'saldo_kredit' => 0,
                        'debet' => $totalAmount,
                        'tipe' => 'WO',
                        'ket' => "Hapus buku {$record['jenis_wo']} AN {$record['nama']}",
                        'userid' => $record['userId'],
                        'status' => "HAPUS BUKU {$record['jenis_wo']}",
                        'cif' => $record['cif'],
                        'ao' => $pembiayaan->cao
                    ]);

                    $pembiayaanData = (array) $pembiayaan;
                    $pembiayaanData['os'] = $totalAmount;
                    DB::table('pembiayaan_wos')->insert($pembiayaanData);

                    DB::table('pembiayaan')
                        ->where('cif', $record['cif'])
                        ->delete();
                }

                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'All records processed successfully'
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing records: ' . $e->getMessage()
            ], 500);
        }
    }
}
