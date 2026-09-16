<?php

namespace App\Services\Setoran;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SetoranPerkelompokService
{
    public function prosesSetoran($code_kel, array $pilihAnggota, array $ambilNilaiNyataSetor, array $inputDebet): array
    {
        DB::beginTransaction();
        try {
            $kelompok = DB::table('pembiayaan')
                ->where('code_kel', $code_kel)
                ->first();

            if (!$kelompok) {
                return ['status' => 404, 'body' => ['message' => 'Kelompok tidak ditemukan']];
            }

            if (empty($pilihAnggota)) {
                return ['status' => 400, 'body' => ['message' => 'Tidak ada anggota yang dipilih']];
            }

            $setoran = DB::table('pembiayaan')
                ->join('anggota', 'pembiayaan.no_anggota', '=', 'anggota.no')
                ->where('code_kel', $code_kel)
                ->whereIn('no_anggota', $pilihAnggota)
                ->select(
                    'pembiayaan.*',
                    'anggota.norek',
                )
                ->get();

            $anggotaDilewati = [];

            foreach ($setoran as $item) {
                $jumlahDebet = $inputDebet[$item->no_anggota] ?? 1;
                $nyataSetorPerDebet = $ambilNilaiNyataSetor[$item->no_anggota] ?? $item->bulat;
                $nyataSetor = $nyataSetorPerDebet * $jumlahDebet;

                $unit = $item->unit;
                $kodeTransaksi = 'BU/' . $unit . strtoupper(Str::random(8));
                $tgl_system = now()->format('Y-m-d');
                $user_id = auth()->user()->id;
                $ket = 'Setoran an ' . $item->nama;

                $tunggakan = DB::table('tunggakan')
                    ->where('cif', $item->cif)
                    ->first();

                if($tunggakan) {
                    // $jumlahTunggakan = $tunggakan->kredit;

                    if ($nyataSetor >= $tunggakan->kredit) {
                        DB::table('tunggakan')->insert([
                            'tgl_tunggak' => $tunggakan->tgl_tunggak,
                            'norek' => $item->norek,
                            'unit' => $unit,
                            'cif' => $item->cif,
                            'code_kel' => $item->code_kel,
                            'debet' => $tunggakan->kredit,
                            'type' => '04',
                            'kredit' => 0,
                            'userid' => $user_id,
                            'ket' => $ket,
                            'cao' => $item->cao,
                            'blok' => '2',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        $sisa = $nyataSetor - $tunggakan->kredit;

                        if ($sisa > 0) {
                            DB::table('simpanan')->insert([
                                'buss_date' => now(),
                                'norek' => $item->norek,
                                'unit' => $item->unit,
                                'cif' => $item->cif,
                                'code_kel' => $item->code_kel,
                                'debet' => 0,
                                'type' => '04',
                                'kredit' => $sisa,
                                'userid' => $user_id,
                                'ket' => $ket,
                                'reff' => generate_reff($unit),
                                'cao' => $item->cao,
                                'blok' => '2',
                                'kode_transaksi' => $kodeTransaksi,
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);
                        }

                    } else {
                        continue;
                    }
                }

                if ($nyataSetorPerDebet < $item->bulat) {
                    DB::table('simpanan')->insert([
                        'buss_date' => now(),
                        'norek' => $item->norek,
                        'unit' => $unit,
                        'cif' => $item->cif,
                        'code_kel' => $item->code_kel,
                        'debet' => 0,
                        'type' => '04',
                        'kredit' => $nyataSetor,
                        'userid' => $user_id,
                        'ket' => $ket,
                        'reff' => generate_reff($unit),
                        'cao' => $item->cao,
                        'blok' => '2',
                        'kode_transaksi' => $kodeTransaksi,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    continue;
                }

                if ($nyataSetor > $item->os) {
                    DB::table('simpanan')->insert([
                        'buss_date' => now(),
                        'norek' => $item->norek,
                        'unit' => $unit,
                        'cif' => $item->cif,
                        'code_kel' => $item->code_kel,
                        'debet' => 0,
                        'type' => '04',
                        'kredit' => $nyataSetor,
                        'userid' => $user_id,
                        'ket' => $ket,
                        'reff' => generate_reff($unit),
                        'cao' => $item->cao,
                        'blok' => '2',
                        'kode_transaksi' => $kodeTransaksi,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    continue;
                }

                DB::table('pembiayaan')
                    ->where('no_anggota', $item->no_anggota)
                    ->update([
                        'run_tenor' => DB::raw("run_tenor + $jumlahDebet"),
                        'ke' => DB::raw("ke + $jumlahDebet"),
                        'last_payment' => now(),
                        'os' => DB::raw("os - $nyataSetor"),
                        'next_schedule' => now()->addDays(7),
                        // 'saldo_margin' => DB::raw("saldo_margin - (ijaroh * $jumlahDebet)")

                    ]);

                DB::table('tabel_transaksi')->insert([
                    [
                        'unit' => $unit,
                        'kode_transaksi' => $kodeTransaksi,
                        'kode_rekening' => '1413000',
                        'tanggal_transaksi' => $tgl_system,
                        'jenis_transaksi' => 'Bukti SYSTEM',
                        'keterangan_transaksi' => $ket,
                        'debet' => 0,
                        'kredit' => $nyataSetor,
                        'tanggal_posting' => $tgl_system,
                        'keterangan_posting' => '',
                        'id_admin' => $user_id,
                        'ip_address' => request()->ip(),
                    ],
                    [
                        'unit' => $unit,
                        'kode_transaksi' => $kodeTransaksi,
                        'kode_rekening' => '1423000',
                        'tanggal_transaksi' => $tgl_system,
                        'jenis_transaksi' => 'Bukti SYSTEM',
                        'keterangan_transaksi' => $ket,
                        'debet' => $item->ijaroh * $jumlahDebet,
                        'kredit' => 0,
                        'tanggal_posting' => $tgl_system,
                        'keterangan_posting' => '',
                        'id_admin' => $user_id,
                        'ip_address' => request()->ip(),
                    ],
                    [
                        'unit' => $unit,
                        'kode_transaksi' => $kodeTransaksi,
                        'kode_rekening' => '41002',
                        'tanggal_transaksi' => $tgl_system,
                        'jenis_transaksi' => 'Bukti SYSTEM',
                        'keterangan_transaksi' => $ket,
                        'debet' => 0,
                        'kredit' => $item->ijaroh * $jumlahDebet,
                        'tanggal_posting' => $tgl_system,
                        'keterangan_posting' => '',
                        'id_admin' => $user_id,
                        'ip_address' => request()->ip(),
                    ],
                    [
                        'unit' => $unit,
                        'kode_transaksi' => $kodeTransaksi,
                        'kode_rekening' => '2101000',
                        'tanggal_transaksi' => $tgl_system,
                        'jenis_transaksi' => 'Bukti SYSTEM',
                        'keterangan_transaksi' => $ket,
                        'debet' => $nyataSetor,
                        'kredit' => 0,
                        'tanggal_posting' => $tgl_system,
                        'keterangan_posting' => '',
                        'id_admin' => $user_id,
                        'ip_address' => request()->ip(),
                    ]
                ]);
            }

            $anggotaSemua = DB::table('pembiayaan')
                ->join('anggota', 'pembiayaan.no_anggota', '=', 'anggota.no')
                ->where('pembiayaan.code_kel', $code_kel)
                ->select('pembiayaan.*', 'anggota.nama', 'anggota.norek')
                ->get();

            $anggotaTidakDipilih = $anggotaSemua->filter(function ($item) use ($pilihAnggota) {
                return !in_array($item->no_anggota, $pilihAnggota);
            });

            foreach ($anggotaTidakDipilih as $item) {
                DB::table('tunggakan')->insert([
                    'tgl_tunggak' => now(),
                    'norek' => $item->norek,
                    'unit' => $item->unit,
                    'cif' => $item->cif,
                    'code_kel' => $item->code_kel,
                    'debet' => 0,
                    'type' => '04',
                    'kredit' => $item->bulat,
                    'userid' => auth()->user()->id,
                    'ket' => 'Tunggakan ' . $item->nama,
                    'cao' => $item->cao,
                    'blok' => '2',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'message' => 'Proses kelompok ' . $code_kel . ' berhasil',
                    'total_diproses' => count($setoran) - count($anggotaDilewati),
                    'anggota_dilewati' => $anggotaDilewati
                ],
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => 500,
                'body' => ['message' => 'Gagal memproses: ' . $e->getMessage()],
            ];
        }
    }
}
