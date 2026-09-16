<?php

namespace App\Services\Realisasi;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RealisasiMusyarokahService
{
    public function realisasikan(array $cekbox): array
    {
        $userid = auth()->user()->id;
        $tgl_system = date('Y-m-d H:i:s');
        $tgl_posting = date('Y-m-d');
        $unit = auth()->user()->unit;

        $batal = [];

        DB::beginTransaction();
        try {
            foreach ($cekbox as $value) {
                $loan = DB::table('temp_akad_mus')
                    ->leftJoin('anggota', 'anggota.cif', '=', 'temp_akad_mus.cif')
                    ->where('temp_akad_mus.cif', $value)
                    ->select([
                        DB::raw('DATE_ADD(tgl_wakalah, INTERVAL 7 DAY) as tgl_murab'),
                        'temp_akad_mus.*', 'anggota.nama'
                    ])
                    ->first();


                if (!$loan) continue;
                $pembiayaan = DB::table('pembiayaan')
                ->where('cif', $value)
                ->first();


                if ($pembiayaan && $pembiayaan->os > 0) {

                    $batal[] = 'CIF ' . $value . ' nama '.$pembiayaan->nama .' masih ada pembiayaan '.$pembiayaan->os.' ';

                    continue;
                }else{


                $kode_trans = 'BU/' . $loan->unit . strtoupper(\Str::random(8));
                $nama = $loan->nama;
                $unit = $loan->unit;
                $code_kel = $loan->code_kel;
                $no_anggota = $loan->no_anggota;
                $norek = $no_anggota;
                $cif = $loan->cif;
                $cao = $loan->cao;
                $tenor = $loan->tenor;
                $pokok = $loan->pokok;
                $margin = $loan->ijaroh;
                $angsuran = $loan->angsuran;
                $bulat = $loan->bulat;
                $plafond = $loan->plafond;
                $os = $loan->os;
                $tgl_skeep=['2025-01-01','2025-01-07'];
                $ket = 'Realisasi Musyarokah atas nama ' . $nama . ' CIF ' . $cif . ' No Anggota ' . $no_anggota;

                $tgl_skeep = DB::table('param_libur')
                ->pluck('tanggal')
                ->map(function ($item) {
                    return Carbon::parse($item)->format('Y-m-d');
                })
                ->toArray();
                $data = [];
                $tgl_bayar = Carbon::parse($loan->next_schedule);
                $angsuran_ke = 1;

                while ($angsuran_ke <= $tenor) {
                    if (in_array($tgl_bayar->format('Y-m-d'), $tgl_skeep)) {
                        $tgl_bayar->addDays(7);
                        continue;
                    }

                    $data[] = [
                        'id_pinjam' => $no_anggota,
                        'angsuran_ke' => $angsuran_ke,
                        'omzet' => 0,
                        'setoran' => $bulat,
                        'angsuran_pokok' => $pokok,
                        'angsuran_margin' => $margin,
                        'tgl_jatpo' => $tgl_bayar->format('Y-m-d'),
                        'tgl_bayar' => $tgl_bayar->format('Y-m-d'),
                        'margin_nisbah' => 0,
                        'cif' => $value,
                        'unit' => $unit,
                        'ao' => $cao,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $angsuran_ke++;
                    $tgl_bayar->addDays(7);
                }





            DB::table('musyarokah_detail')->insert($data);

            $simpanTransaksi=[
                [
                    'unit' => $unit,
                    'kode_transaksi' => $kode_trans,
                    'kode_rekening' => '1472000',
                    'tanggal_transaksi' => $tgl_system,
                    'jenis_transaksi' => 'Bukti SYSTEM',
                    'keterangan_transaksi' => $ket,
                    'debet' => $plafond,
                    'kredit' => '0',
                    'tanggal_posting' => $tgl_posting,
                    'keterangan_posting' => '',
                    'id_admin' => $userid,
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'unit' => $unit,
                    'kode_transaksi' => $kode_trans,
                    'kode_rekening' => '1120000',
                    'tanggal_transaksi' => $tgl_system,
                    'jenis_transaksi' => 'Bukti SYSTEM',
                    'keterangan_transaksi' => $ket,
                    'kredit' => $plafond,
                    'debet' => '0',
                    'tanggal_posting' => $tgl_posting,
                    'keterangan_posting' => '',
                    'id_admin' => $userid,
                    'ip_address' => request()->ip(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ];
            $rekloan = [
                'tgl_realisasi' => $tgl_system,
                'unit' => $unit,
                'no_anggota' => $no_anggota,
                'saldo_kredit' => $os,
                'debet' => 0,
                'tipe' => 'M001',
                'ket' => "Realisasi Murabahah AN {$nama}",
                'userid' => $userid,
                'status' => 'REALISASI MUSYAROKAH',
                'cif' => $cif,
                'ao' => $cao
            ];

            DB::table('rek_loan')->insert($rekloan);


            $simpanan = [
                [
                    'reff' => generate_reff($unit),
                    'buss_date' => $tgl_system,
                    'norek' => $no_anggota,
                    'unit' => $unit,
                    'cif' => $cif,
                    'code_kel' => $code_kel,
                    'debet' => $plafond,
                    'type' => '01',
                    'kredit' => 0,
                    'userid' => $userid,
                    'ket' => "Realisasi Musyarakah AN {$nama}",
                    'cao' => $cao,
                    'blok' => 0,
                    'tgl_input' => date('Y-m-d'),
                    'kode_transaksi' => $kode_trans,
                ],
                [
                    'reff' => generate_reff($unit),
                    'buss_date' => $tgl_system,
                    'norek' => $no_anggota,
                    'unit' => $unit,
                    'cif' => $cif,
                    'code_kel' => $code_kel,
                    'debet' => 0,
                    'type' => '01',
                    'kredit' => $plafond,
                    'userid' => $userid,
                    'ket' => "Realisasi Musyarakah AN {$nama}",
                    'cao' => $cao,
                    'blok' => 0,
                    'tgl_input' => date('Y-m-d'),
                    'kode_transaksi' => $kode_trans,
                ]
            ];
            DB::table('simpanan')->insert($simpanan);
            DB::table('jurnal_umum')->insert([
                'nomor_jurnal' => null,
                'kode_transaksi' => $kode_trans,
                'tanggal_selesai' => $tgl_system,
                'unit' => $unit
            ]);


            DB::table('tabel_transaksi')->insert($simpanTransaksi);
            DB::statement('delete from pembiayaan where cif = ?', [$value]);
            // Kolom disebutkan eksplisit (bukan SELECT *) karena `pembiayaan` sudah punya
            // kolom tambahan (deleted_at, created_by, updated_by, ip_address dari Sesi 1.2/1.3)
            // yang tidak ada di `temp_akad_mus`, sehingga SELECT * akan mismatch jumlah kolom.
            $kolomAkad = 'buss_date, code_kel, no_anggota, cif, nama, deal_type, suffix, bagi_hasil, tenor, plafond, os, saldo_margin, angsuran, pokok, ijaroh, bulat, run_tenor, ke, usaha, nama_usaha, unit, tgl_wakalah, tgl_akad, tgl_murab, next_schedule, maturity_date, last_payment, hari, cao, userid, status, status_usia, status_app, gol, deal_produk, persen_margin, created_at, updated_at';
            DB::statement("INSERT INTO pembiayaan ($kolomAkad) SELECT $kolomAkad FROM temp_akad_mus where cif = ?", [$value]);
            DB::table('temp_akad_mus')->where('cif', $value)->delete();




            }
        }

            DB::commit();

            return [
                'status' => 200,
                'body' => [
                    'success' => true,
                    'message' => 'Musyarokah berhasil direalisasikan',
                    'batal' => $batal,
                ],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => 500,
                'body' => [
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ],
            ];
        }
    }
}
