<?php

namespace App\Services\Realisasi;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RealisasiMurabahahService
{
    public function updateStatus(array $validated): array
    {
        $failedCifs = [];

        try {
            DB::beginTransaction();

            $akadRecords = DB::table('temp_akad_mus')
                ->whereIn('cif', $validated['cifs'])
                ->get();

            $tanggalLibur = DB::table('param_tgl')
                ->pluck('param_tgl')
                ->toArray();

            foreach ($akadRecords as $akad) {
                try {
                    $existingRecord = DB::table('pembiayaan')
                        ->where('cif', $akad->cif)
                        ->first();

                    if ($existingRecord) {
                        if ($existingRecord->os > 0) {
                            continue;
                        } else {
                            DB::table('pembiayaan')
                                ->where('cif', $existingRecord->cif)
                                ->update((array) $akad);
                        }
                    } else {
                        DB::table('pembiayaan')->insert((array) $akad);
                    }

                    $tglJatuhTempo = [];
                    $currentDate = Carbon::now()->addDays(7);

                    for ($i = 0; $i < $akad->tenor; $i++) {
                        $tglJatuhTempo[] = $currentDate->format('Y-m-d H:i:s');
                        $currentDate->addDays(7);
                    }

                    $adjustedTglJatuhTempo = [];

                    foreach ($tglJatuhTempo as $date) {
                        $formattedDate = Carbon::parse($date)->format('Y-m-d');

                        while (in_array($formattedDate, $tanggalLibur)) {
                            $date = Carbon::parse(end($adjustedTglJatuhTempo))->addDays(7)->format('Y-m-d H:i:s');
                            $formattedDate = Carbon::parse($date)->format('Y-m-d');
                        }

                        $adjustedTglJatuhTempo[] = $date;
                    }

                    foreach ($adjustedTglJatuhTempo as $index => $date) {
                        $cicilan = $index + 1;

                        DB::table('pembiayaan_detail')->insert([
                            'id' => null,
                            'id_pinjam' => $akad->no_anggota,
                            'cicilan' => $cicilan,
                            'angsuran_pokok' => $akad->pokok,
                            'margin' => $akad->ijaroh,
                            'tgl_jatuh_tempo' => $date,
                            'tgl_bayar' => null,
                            'jumlah_bayar' => $akad->bulat,
                            'keterangan' => '',
                            'cif' => $akad->cif,
                            'unit' => $validated['unit'],
                            'ao' => $akad->cao,
                            'code_kel' => $validated['kode_kel'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }

                    $transaksiData = [
                        [
                            'id_transaksi' => null,
                            'unit' => $validated['unit'],
                            'kode_transaksi' => "BS-{$validated['unit']}-" . Str::random(7),
                            'kode_rekening' => 1481000,
                            'tanggal_transaksi' => date('Y-m-d H:i:s', strtotime($validated['param_tanggal'])),
                            'jenis_transaksi' => 'bukti SYSTEM',
                            'keterangan_transaksi' => "Persediaan Murabahah AN {$akad->nama}",
                            'debet' => $akad->plafond,
                            'kredit' => 0,
                            'tanggal_posting' => date('Y-m-d'),
                            'keterangan_posting' => '',
                            'id_admin' => $validated['id'],
                            'ip_address' => request()->ip(),
                        ],
                        [
                            'id_transaksi' => null,
                            'unit' => $validated['unit'],
                            'kode_transaksi' => "BS-{$validated['unit']}-" . Str::random(7),
                            'kode_rekening' => 1431000,
                            'tanggal_transaksi' => date('Y-m-d H:i:s', strtotime($validated['param_tanggal'])),
                            'jenis_transaksi' => 'bukti SYSTEM',
                            'keterangan_transaksi' => "Piutang Wakalah AN {$akad->nama}",
                            'debet' => 0,
                            'kredit' => $akad->plafond,
                            'tanggal_posting' => date('Y-m-d'),
                            'keterangan_posting' => '',
                            'id_admin' => $validated['id'],
                            'ip_address' => request()->ip(),
                        ],
                        [
                            'id_transaksi' => null,
                            'unit' => $validated['unit'],
                            'kode_transaksi' => "BS-{$validated['unit']}-" . Str::random(7),
                            'kode_rekening' => 1413000,
                            'tanggal_transaksi' => date('Y-m-d H:i:s', strtotime($validated['param_tanggal'])),
                            'jenis_transaksi' => 'bukti SYSTEM',
                            'keterangan_transaksi' => "Piutang Murabahah Mingguan AN {$akad->nama}",
                            'debet' => $akad->plafond + $akad->saldo_margin,
                            'kredit' => 0,
                            'tanggal_posting' => date('Y-m-d'),
                            'keterangan_posting' => '',
                            'id_admin' => $validated['id'],
                            'ip_address' => request()->ip(),
                        ],
                        [
                            'id_transaksi' => null,
                            'unit' => $validated['unit'],
                            'kode_transaksi' => "BS-{$validated['unit']}-" . Str::random(7),
                            'kode_rekening' => 1481000,
                            'tanggal_transaksi' => date('Y-m-d H:i:s', strtotime($validated['param_tanggal'])),
                            'jenis_transaksi' => 'bukti SYSTEM',
                            'keterangan_transaksi' => "Persediaan Murabahah AN {$akad->nama}",
                            'debet' => 0,
                            'kredit' => $akad->plafond,
                            'tanggal_posting' => date('Y-m-d'),
                            'keterangan_posting' => '',
                            'id_admin' => $validated['id'],
                            'ip_address' => request()->ip(),
                        ],
                        [
                            'id_transaksi' => null,
                            'unit' => $validated['unit'],
                            'kode_transaksi' => "BS-{$validated['unit']}-" . Str::random(7),
                            'kode_rekening' => 1423000,
                            'tanggal_transaksi' => date('Y-m-d H:i:s', strtotime($validated['param_tanggal'])),
                            'jenis_transaksi' => 'bukti SYSTEM',
                            'keterangan_transaksi' => "PMYD Murabahah Mingguan AN {$akad->nama}",
                            'debet' => 0,
                            'kredit' => $akad->saldo_margin,
                            'tanggal_posting' => date('Y-m-d'),
                            'keterangan_posting' => '',
                            'id_admin' => $validated['id'],
                            'ip_address' => request()->ip(),
                        ]
                    ];
                    DB::table('tabel_transaksi')->insert($transaksiData);

                    DB::table('rek_loan')->insert([
                        'ref' => null,
                        'tgl_realisasi' => date('Y-m-d H:i:s', strtotime($validated['param_tanggal'])),
                        'unit' => $validated['unit'],
                        'no_anggota' => $akad->no_anggota,
                        'saldo_kredit' => $akad->os,
                        'debet' => 0,
                        'tipe' => 'L001',
                        'ket' => "Realisasi Murabahah AN {$akad->nama}",
                        'userid' => $validated['id'],
                        'status' => 'REALISASI MURABAHAH',
                        'cif' => $akad->cif,
                        'ao' => $akad->cao
                    ]);

                    DB::table('jurnal_umum')->insert([
                        'nomor_jurnal' => null,
                        'kode_transaksi' => "BS-{$validated['unit']}-" . Str::random(7),
                        'tanggal_selesai' => $validated['param_tanggal'],
                        'unit' => $validated['unit']
                    ]);

                    $simpananData = [
                        [
                            'reff' => generate_reff($validated['unit']),
                            'buss_date' => date('Y-m-d H:i:s', strtotime($validated['param_tanggal'])),
                            'norek' => $akad->no_anggota,
                            'unit' => $validated['unit'],
                            'cif' => $akad->cif,
                            'code_kel' => $validated['kode_kel'],
                            'debet' => $akad->plafond,
                            'type' => '01',
                            'kredit' => 0,
                            'userid' => $validated['id'],
                            'ket' => "Realisasi Murabahah AN {$akad->nama}",
                            'cao' => $akad->cao,
                            'blok' => 0,
                            'tgl_input' => date('Y-m-d'),
                            'kode_transaksi' => "BS-{$validated['unit']}-" . Str::random(7),
                        ],
                        [
                            'reff' => generate_reff($validated['unit']),
                            'buss_date' => date('Y-m-d H:i:s', strtotime($validated['param_tanggal'])),
                            'norek' => $akad->no_anggota,
                            'unit' => $validated['unit'],
                            'cif' => $akad->cif,
                            'code_kel' => $validated['kode_kel'],
                            'debet' => 0,
                            'type' => '01',
                            'kredit' => $akad->plafond,
                            'userid' => $validated['id'],
                            'ket' => "Realisasi Murabahah AN {$akad->nama}",
                            'cao' => $akad->cao,
                            'blok' => 0,
                            'tgl_input' => date('Y-m-d'),
                            'kode_transaksi' => "BS-{$validated['unit']}-" . Str::random(7),
                        ]
                    ];
                    DB::table('simpanan')->insert($simpananData);

                    DB::table('temp_akad_mus')
                        ->where('cif', $akad->cif)
                        ->delete();

                } catch (\Exception $e) {
                    $failedCifs[] = ['cif' => $akad->cif, 'nama' => $akad->nama];
                }
            }

            if (count($failedCifs) === count($akadRecords)) {
                DB::rollBack();
                return ['status' => 500, 'body' => ['error' => 'Everything failed']];
            }

            if (!empty($failedCifs)) {
                DB::commit();
                return [
                    'status' => 207,
                    'body' => [
                        'message' => 'Partial success',
                        'failed_cifs' => $failedCifs
                    ],
                ];
            }

            DB::commit();
            return ['status' => 200, 'body' => ['message' => 'Everything was successful']];

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::critical('Realisasi update error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [
                'status' => 500,
                'body' => ['error' => $e->getMessage() . ' - Line: ' . $e->getLine()],
            ];
        }
    }
}
