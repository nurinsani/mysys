<?php

namespace App\Services\Transaksi;

use App\Models\simpanan;
use App\Models\simpanan_pokok;
use App\Models\simpanan_wajib;
use Illuminate\Support\Facades\DB;

class TransaksiService
{
    public function simpanTransaksi(array $validated): array
    {
        try {
            DB::beginTransaction();

            $unit = auth()->user()->unit;
            $user_id = auth()->user()->id;

            $nominal = $validated['nominal'];
            $cif = $validated['cif'];
            $jenisTransaksi = $validated['jenis_transaksi'];
            $keterangan = $validated['keterangan'] ?? null;
            $tgl_system = date('Y-m-d');


            $anggota = DB::table('anggota')
                ->where('cif', $cif)
                ->first();

            if (!$anggota) {
                throw new \Exception('Data nasabah tidak ditemukan');
            }

            $norek = $anggota->norek;
            $cao = $anggota->cao;
            $kode_kel = $anggota->kode_kel;

            $kodeTransaksi = 'BU/' . $anggota->unit . strtoupper(\Str::random(8));
            $reff = generate_reff($unit);

            // Untuk transaksi simpanan pokok/wajib
            if ($jenisTransaksi == 1) {
                $hasPokok = simpanan_pokok::where('cif', $cif)->exists();

                if (!$hasPokok) {
                    $simpananPokok = simpanan_pokok::create([
                        'reff' => $reff,
                        'buss_date' => now(),
                        'norek' => $norek,
                        'unit' => $unit,
                        'cif' => $validated['cif'],
                        'code_kel' => $kode_kel,
                        'debet' => 0,
                        'type' => '01',
                        'kredit' => 50000,
                        'userid' => $user_id,
                        'ket' => $validated['keterangan'],
                        'cao' => $cao,
                        'blok' => '1',
                        'tgl_input' => now(),
                        'kode_transaksi' => $kodeTransaksi,
                    ]);

                    $sisaWajib = $nominal - 50000;

                    if ($sisaWajib > 0) {
                        $simpananWajib = simpanan_wajib::create([
                            'reff' => $reff,
                            'buss_date' => now(),
                            'norek' => $norek,
                            'unit' => $unit,
                            'cif' => $validated['cif'],
                            'code_kel' => $kode_kel,
                            'debet' => 0,
                            'type' => '01',
                            'kredit' => $sisaWajib,
                            'userid' => $user_id,
                            'ket' => $validated['keterangan'],
                            'cao' => $cao,
                            'blok' => '1',
                            'tgl_input' => now(),
                            'kode_transaksi' => $kodeTransaksi,
                        ]);

                        $transaksi_wajib = [
                            [
                                'unit' => $unit,
                                'kode_transaksi' => $kodeTransaksi,
                                'kode_rekening' => '1120000',
                                'tanggal_transaksi' => $tgl_system,
                                'jenis_transaksi' => 'Bukti SYSTEM',
                                'keterangan_transaksi' => 'Setoran An ' . $anggota->nama,
                                'debet' => $sisaWajib,
                                'kredit' => '0',
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
                                'keterangan_transaksi' => 'Setoran An ' . $anggota->nama,
                                'debet' => 0,
                                'kredit' => $sisaWajib,
                                'tanggal_posting' => $tgl_system,
                                'keterangan_posting' => '',
                                'id_admin' => $user_id,
                                'ip_address' => request()->ip(),
                            ]

                        ];

                        DB::table('tabel_transaksi')->insert($transaksi_wajib);

                        $transaksi_pokok = [
                            [
                                'unit' => $unit,
                                'kode_transaksi' => $kodeTransaksi,
                                'kode_rekening' => '1120000', // kas unit
                                'tanggal_transaksi' => $tgl_system,
                                'jenis_transaksi' => 'Bukti SYSTEM',
                                'keterangan_transaksi' => 'Setoran Pokok An ' . $anggota->nama,
                                'debet' => 50000,
                                'kredit' => '0',
                                'tanggal_posting' => $tgl_system,
                                'keterangan_posting' => '',
                                'id_admin' => $user_id,
                                'ip_address' => request()->ip(),
                            ],
                            [
                                'unit' => $unit,
                                'kode_transaksi' => $kodeTransaksi,
                                'kode_rekening' => '3102000', // SP-Anggota
                                'tanggal_transaksi' => $tgl_system,
                                'jenis_transaksi' => 'Bukti SYSTEM',
                                'keterangan_transaksi' => 'Setoran Pokok An ' . $anggota->nama,
                                'debet' => 0,
                                'kredit' => 50000,
                                'tanggal_posting' => $tgl_system,
                                'keterangan_posting' => '',
                                'id_admin' => $user_id,
                                'ip_address' => request()->ip(),
                            ],
                            [
                                'unit' => $unit,
                                'kode_transaksi' => $kodeTransaksi,
                                'kode_rekening' => '1120000', // kas unit
                                'tanggal_transaksi' => $tgl_system,
                                'jenis_transaksi' => 'Bukti SYSTEM',
                                'keterangan_transaksi' => 'Setoran Pokok An ' . $anggota->nama,
                                'debet' => 50000,
                                'kredit' => '0',
                                'tanggal_posting' => $tgl_system,
                                'keterangan_posting' => '',
                                'id_admin' => $user_id,
                                'ip_address' => request()->ip(),
                            ],
                            [
                                'unit' => $unit,
                                'kode_transaksi' => $kodeTransaksi,
                                'kode_rekening' => '3202000', // SW-Anggota
                                'tanggal_transaksi' => $tgl_system,
                                'jenis_transaksi' => 'Bukti SYSTEM',
                                'keterangan_transaksi' => 'Setoran Pokok An ' . $anggota->nama,
                                'debet' => 0,
                                'kredit' => 50000,
                                'tanggal_posting' => $tgl_system,
                                'keterangan_posting' => '',
                                'id_admin' => $user_id,
                                'ip_address' => request()->ip(),
                            ]

                        ];

                        DB::table('tabel_transaksi')->insert($transaksi_pokok);
                    }

                    DB::commit();

                    return [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Transaksi berhasil disimpan (50rb untuk Pokok, ' . $sisaWajib . ' untuk Wajib)',
                            'data' => [
                                'pokok' => $simpananPokok,
                                'wajib' => $simpananWajib ?? null
                            ]
                        ],
                    ];
                } else if ($hasPokok) {
                    $simpananWajib = simpanan_wajib::create([
                        'reff' => $reff,
                        'buss_date' => now(),
                        'norek' => $norek,
                        'unit' => $unit,
                        'cif' => $validated['cif'],
                        'code_kel' => $kode_kel,
                        'debet' => 0,
                        'type' => '01',
                        'kredit' => $nominal,
                        'userid' => $user_id,
                        'ket' => $validated['keterangan'],
                        'cao' => $cao,
                        'blok' => '1',
                        'tgl_input' => now(),
                        'kode_transaksi' => $kodeTransaksi,
                    ]);

                    $transaksi = [
                        [
                            'unit' => $unit,
                            'kode_transaksi' => $kodeTransaksi,
                            'kode_rekening' => '1120000',
                            'tanggal_transaksi' => $tgl_system,
                            'jenis_transaksi' => 'Bukti SYSTEM',
                            'keterangan_transaksi' => 'Setoran 5% An ' . $anggota->nama,
                            'debet' => $nominal,
                            'kredit' => '0',
                            'tanggal_posting' => $tgl_system,
                            'keterangan_posting' => '',
                            'id_admin' => $user_id,
                            'ip_address' => request()->ip(),
                        ],
                        [
                            'unit' => $unit,
                            'kode_transaksi' => $kodeTransaksi,
                            'kode_rekening' => '3102000',
                            'tanggal_transaksi' => $tgl_system,
                            'jenis_transaksi' => 'Bukti SYSTEM',
                            'keterangan_transaksi' => 'Setoran 5% An ' . $anggota->nama,
                            'debet' => 0,
                            'kredit' => $nominal,
                            'tanggal_posting' => $tgl_system,
                            'keterangan_posting' => '',
                            'id_admin' => $user_id,
                            'ip_address' => request()->ip(),
                        ]
                    ];

                    DB::table('tabel_transaksi')->insert($transaksi);

                    DB::commit();

                    return [
                        'status' => 200,
                        'body' => [
                            'success' => true,
                            'message' => 'Transaksi simpanan wajib berhasil disimpan',
                            'data' => $simpananWajib
                        ],
                    ];
                }
            } elseif ($jenisTransaksi == 2) {
                // penarikan tunai
                $transaksi = simpanan::create([
                    'reff' => $reff,
                    'buss_date' => now(),
                    'norek' => $norek,
                    'unit' => $unit,
                    'cif' => $validated['cif'],
                    'code_kel' => $kode_kel,
                    'debet' => $nominal,
                    'type' => '01',
                    'kredit' => 0,
                    'userid' => $user_id,
                    'ket' => $validated['keterangan'],
                    'cao' => $cao,
                    'blok' => '1',
                    'tgl_input' => now(),
                    'kode_transaksi' => $kodeTransaksi,
                ]);

                $transaksi_tarik_tunai = [
                    [
                        'unit' => $unit,
                        'kode_transaksi' => $kodeTransaksi,
                        'kode_rekening' => '1120000',
                        'tanggal_transaksi' => $tgl_system,
                        'jenis_transaksi' => 'Bukti SYSTEM',
                        'keterangan_transaksi' => 'Penarikan Simpanan An ' . $anggota->nama,
                        'debet' => $nominal,
                        'kredit' => '0',
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
                        'keterangan_transaksi' => 'Penarikan Simpanan An ' . $anggota->nama,
                        'debet' => 0,
                        'kredit' => $nominal,
                        'tanggal_posting' => $tgl_system,
                        'keterangan_posting' => '',
                        'id_admin' => $user_id,
                        'ip_address' => request()->ip(),
                    ]
                ];

                DB::table('tabel_transaksi')->insert($transaksi_tarik_tunai);

                DB::commit();

                return [
                    'status' => 200,
                    'body' => [
                        'success' => true,
                        'message' => 'Transaksi berhasil disimpan',
                        'data' => $transaksi
                    ],
                ];
            } elseif ($jenisTransaksi == 3) {
                // setor angsuran
                $transaksi = simpanan::create([
                    'reff' => $reff,
                    'buss_date' => now(),
                    'norek' => $norek,
                    'unit' => $unit,
                    'cif' => $validated['cif'],
                    'code_kel' => $kode_kel,
                    'debet' => 0,
                    'type' => '01',
                    'kredit' => $nominal,
                    'userid' => $user_id,
                    'ket' => $validated['keterangan'],
                    'cao' => $cao,
                    'blok' => '1',
                    'tgl_input' => now(),
                    'kode_transaksi' => $kodeTransaksi,
                ]);

                $transaksi_setor_angsuran = [
                    [
                        'unit' => $unit,
                        'kode_transaksi' => $kodeTransaksi,
                        'kode_rekening' => '1120000',
                        'tanggal_transaksi' => $tgl_system,
                        'jenis_transaksi' => 'Bukti SYSTEM',
                        'keterangan_transaksi' => 'Setoran Angsuran An ' . $anggota->nama,
                        'debet' => $nominal,
                        'kredit' => '0',
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
                        'keterangan_transaksi' => 'Setoran Angsuran An ' . $anggota->nama,
                        'debet' => 0,
                        'kredit' => $nominal,
                        'tanggal_posting' => $tgl_system,
                        'keterangan_posting' => '',
                        'id_admin' => $user_id,
                        'ip_address' => request()->ip(),
                    ]
                ];

                DB::table('tabel_transaksi')->insert($transaksi_setor_angsuran);

                DB::commit();

                return [
                    'status' => 200,
                    'body' => [
                        'success' => true,
                        'message' => 'Transaksi berhasil disimpan',
                        'data' => $transaksi
                    ],
                ];
            } elseif ($jenisTransaksi == 4) {
                // pemindah bukuan
                if ($validated['jenis_pemindahan'] == 'debet' && $validated['jenis_simpanan'] == 'pokok') {
                    // jika jenis pemindahnya debet pokok = maka saldo wadiah akan bertambah sesuai nominal yang di inputkan dari pokok
                    $transaksi = simpanan::create([
                        'reff' => $reff,
                        'buss_date' => now(),
                        'norek' => $norek,
                        'unit' => $unit,
                        'cif' => $validated['cif'],
                        'code_kel' => $kode_kel,
                        'debet' => 0,
                        'type' => '01',
                        'kredit' => $nominal,
                        'userid' => $user_id,
                        'ket' => $validated['keterangan'],
                        'cao' => $cao,
                        'blok' => '1',
                        'tgl_input' => now(),
                        'kode_transaksi' => $kodeTransaksi,
                    ]);

                    $transaksi = simpanan_pokok::create([
                        'reff' => $reff,
                        'buss_date' => now(),
                        'norek' => $norek,
                        'unit' => $unit,
                        'cif' => $validated['cif'],
                        'code_kel' => $kode_kel,
                        'debet' => $nominal,
                        'type' => '01',
                        'kredit' => 0,
                        'userid' => $user_id,
                        'ket' => $validated['keterangan'],
                        'cao' => $cao,
                        'blok' => '1',
                        'tgl_input' => now(),
                        'kode_transaksi' => $kodeTransaksi,
                    ]);

                    $transaksi_pemindahbukuan = [
                        [
                            'unit' => $unit,
                            'kode_transaksi' => $kodeTransaksi,
                            'kode_rekening' => '3102000', // SP-Anggota
                            'tanggal_transaksi' => $tgl_system,
                            'jenis_transaksi' => 'Bukti SYSTEM',
                            'keterangan_transaksi' => 'Pemindahbukuan Pokok An ' . $anggota->nama,
                            'debet' => $nominal,
                            'kredit' => '0',
                            'tanggal_posting' => $tgl_system,
                            'keterangan_posting' => '',
                            'id_admin' => $user_id,
                            'ip_address' => request()->ip(),
                        ],
                        [
                            'unit' => $unit,
                            'kode_transaksi' => $kodeTransaksi,
                            'kode_rekening' => '2101000', // Simpanan Wadiah Kelompok
                            'tanggal_transaksi' => $tgl_system,
                            'jenis_transaksi' => 'Bukti SYSTEM',
                            'keterangan_transaksi' => 'Pemindahbukuan Pokok An ' . $anggota->nama,
                            'debet' => 0,
                            'kredit' => $nominal,
                            'tanggal_posting' => $tgl_system,
                            'keterangan_posting' => '',
                            'id_admin' => $user_id,
                            'ip_address' => request()->ip(),
                        ]
                    ];
                } elseif ($validated['jenis_pemindahan'] == 'debet' && $validated['jenis_simpanan'] == 'wajib') {
                    // jika jenis pemindahnya debet wajib = maka saldo wadiah akan bertambah sesuai nominal yang di inputkan dari wajib
                    $transaksi = simpanan::create([
                        'reff' => $reff,
                        'buss_date' => now(),
                        'norek' => $norek,
                        'unit' => $unit,
                        'cif' => $validated['cif'],
                        'code_kel' => $kode_kel,
                        'debet' => 0,
                        'type' => '01',
                        'kredit' => $nominal,
                        'userid' => $user_id,
                        'ket' => $validated['keterangan'],
                        'cao' => $cao,
                        'blok' => '1',
                        'tgl_input' => now(),
                        'kode_transaksi' => $kodeTransaksi,
                    ]);

                    $transaksi = simpanan_wajib::create([
                        'reff' => $reff,
                        'buss_date' => now(),
                        'norek' => $norek,
                        'unit' => $unit,
                        'cif' => $validated['cif'],
                        'code_kel' => $kode_kel,
                        'debet' => $nominal,
                        'type' => '01',
                        'kredit' => 0,
                        'userid' => $user_id,
                        'ket' => $validated['keterangan'],
                        'cao' => $cao,
                        'blok' => '1',
                        'tgl_input' => now(),
                        'kode_transaksi' => $kodeTransaksi,
                    ]);

                    $transaksi_pemindahbukuan = [
                        [
                            'unit' => $unit,
                            'kode_transaksi' => $kodeTransaksi,
                            'kode_rekening' => '3202000', // SW-Anggota
                            'tanggal_transaksi' => $tgl_system,
                            'jenis_transaksi' => 'Bukti SYSTEM',
                            'keterangan_transaksi' => 'Pemindahbukuan Wajib An ' . $anggota->nama,
                            'debet' => $nominal,
                            'kredit' => '0',
                            'tanggal_posting' => $tgl_system,
                            'keterangan_posting' => '',
                            'id_admin' => $user_id,
                            'ip_address' => request()->ip(),
                        ],
                        [
                            'unit' => $unit,
                            'kode_transaksi' => $kodeTransaksi,
                            'kode_rekening' => '2101000', // Simpanan Wadiah Kelompok
                            'tanggal_transaksi' => $tgl_system,
                            'jenis_transaksi' => 'Bukti SYSTEM',
                            'keterangan_transaksi' => 'Pemindahbukuan Wajib An ' . $anggota->nama,
                            'debet' => 0,
                            'kredit' => $nominal,
                            'tanggal_posting' => $tgl_system,
                            'keterangan_posting' => '',
                            'id_admin' => $user_id,
                            'ip_address' => request()->ip(),
                        ]
                    ];
                } elseif ($validated['jenis_pemindahan'] == 'kredit' && $validated['jenis_simpanan'] == 'pokok') {
                    // jika jenis pemindahnya kredit pokok	= maka saldo wadiah akan berkurang sesuai nominal yang di inputkan ke pokok
                    $transaksi = simpanan::create([
                        'reff' => $reff,
                        'buss_date' => now(),
                        'norek' => $norek,
                        'unit' => $unit,
                        'cif' => $validated['cif'],
                        'code_kel' => $kode_kel,
                        'debet' => $nominal,
                        'type' => '01',
                        'kredit' => 0,
                        'userid' => $user_id,
                        'ket' => $validated['keterangan'],
                        'cao' => $cao,
                        'blok' => '1',
                        'tgl_input' => now(),
                        'kode_transaksi' => $kodeTransaksi,
                    ]);

                    $transaksi = simpanan_pokok::create([
                        'reff' => $reff,
                        'buss_date' => now(),
                        'norek' => $norek,
                        'unit' => $unit,
                        'cif' => $validated['cif'],
                        'code_kel' => $kode_kel,
                        'debet' => 0,
                        'type' => '01',
                        'kredit' => $nominal,
                        'userid' => $user_id,
                        'ket' => $validated['keterangan'],
                        'cao' => $cao,
                        'blok' => '1',
                        'tgl_input' => now(),
                        'kode_transaksi' => $kodeTransaksi,
                    ]);

                    $transaksi_pemindahbukuan = [
                        [
                            'unit' => $unit,
                            'kode_transaksi' => $kodeTransaksi,
                            'kode_rekening' => '3102000', // SP-Anggota
                            'tanggal_transaksi' => $tgl_system,
                            'jenis_transaksi' => 'Bukti SYSTEM',
                            'keterangan_transaksi' => 'Pemindahbukuan Pokok An ' . $anggota->nama,
                            'debet' => $nominal,
                            'kredit' => '0',
                            'tanggal_posting' => $tgl_system,
                            'keterangan_posting' => '',
                            'id_admin' => $user_id,
                            'ip_address' => request()->ip(),
                        ],
                        [
                            'unit' => $unit,
                            'kode_transaksi' => $kodeTransaksi,
                            'kode_rekening' => '2101000', // Simpanan Wadiah Kelompok
                            'tanggal_transaksi' => $tgl_system,
                            'jenis_transaksi' => 'Bukti SYSTEM',
                            'keterangan_transaksi' => 'Pemindahbukuan Pokok An ' . $anggota->nama,
                            'debet' => 0,
                            'kredit' => $nominal,
                            'tanggal_posting' => $tgl_system,
                            'keterangan_posting' => '',
                            'id_admin' => $user_id,
                            'ip_address' => request()->ip(),
                        ]
                    ];
                } elseif ($validated['jenis_pemindahan'] == 'kredit' && $validated['jenis_simpanan'] == 'wajib') {
                    // jika jenis pemindahnya kredit wajib	= maka saldo wadiah akan berkurang sesuai nominal yang di inputkan, saldo wajib bertambah
                    $transaksi = simpanan::create([
                        'reff' => $reff,
                        'buss_date' => now(),
                        'norek' => $norek,
                        'unit' => $unit,
                        'cif' => $validated['cif'],
                        'code_kel' => $kode_kel,
                        'debet' => $nominal,
                        'type' => '01',
                        'kredit' => 0,
                        'userid' => $user_id,
                        'ket' => $validated['keterangan'],
                        'cao' => $cao,
                        'blok' => '1',
                        'tgl_input' => now(),
                        'kode_transaksi' => $kodeTransaksi,
                    ]);

                    $transaksi = simpanan_wajib::create([
                        'reff' => $reff,
                        'buss_date' => now(),
                        'norek' => $norek,
                        'unit' => $unit,
                        'cif' => $validated['cif'],
                        'code_kel' => $kode_kel,
                        'debet' => 0,
                        'type' => '01',
                        'kredit' => $nominal,
                        'userid' => $user_id,
                        'ket' => $validated['keterangan'],
                        'cao' => $cao,
                        'blok' => '1',
                        'tgl_input' => now(),
                        'kode_transaksi' => $kodeTransaksi,
                    ]);

                    $transaksi_pemindahbukuan = [
                        [
                            'unit' => $unit,
                            'kode_transaksi' => $kodeTransaksi,
                            'kode_rekening' => '3102000', // SP-Anggota
                            'tanggal_transaksi' => $tgl_system,
                            'jenis_transaksi' => 'Bukti SYSTEM',
                            'keterangan_transaksi' => 'Pemindahbukuan Pokok An ' . $anggota->nama,
                            'debet' => $nominal,
                            'kredit' => '0',
                            'tanggal_posting' => $tgl_system,
                            'keterangan_posting' => '',
                            'id_admin' => $user_id,
                            'ip_address' => request()->ip(),
                        ],
                        [
                            'unit' => $unit,
                            'kode_transaksi' => $kodeTransaksi,
                            'kode_rekening' => '2101000', // Simpanan Wadiah Kelompok
                            'tanggal_transaksi' => $tgl_system,
                            'jenis_transaksi' => 'Bukti SYSTEM',
                            'keterangan_transaksi' => 'Pemindahbukuan Pokok An ' . $anggota->nama,
                            'debet' => 0,
                            'kredit' => $nominal,
                            'tanggal_posting' => $tgl_system,
                            'keterangan_posting' => '',
                            'id_admin' => $user_id,
                            'ip_address' => request()->ip(),
                        ]
                    ];
                }

                DB::table('tabel_transaksi')->insert($transaksi_pemindahbukuan);


                DB::commit();

                return [
                    'status' => 200,
                    'body' => [
                        'success' => true,
                        'message' => 'Transaksi berhasil disimpan',
                        'data' => $transaksi
                    ],
                ];
            } elseif ($jenisTransaksi == 5) {
                // setoran angsuran WO
                $transaksi_setor_angsuran_wo = [
                    [
                        'unit' => $unit,
                        'kode_transaksi' => $kodeTransaksi,
                        'kode_rekening' => '1120000', // kas unit
                        'tanggal_transaksi' => $tgl_system,
                        'jenis_transaksi' => 'Bukti SYSTEM',
                        'keterangan_transaksi' => 'Setoran Angsuran WO An ' . $anggota->nama,
                        'debet' => $nominal,
                        'kredit' => '0',
                        'tanggal_posting' => $tgl_system,
                        'keterangan_posting' => '',
                        'id_admin' => $user_id,
                        'ip_address' => request()->ip(),
                    ],
                    [
                        'unit' => $unit,
                        'kode_transaksi' => $kodeTransaksi,
                        'kode_rekening' => '42356', // POL-Penerimaan Kembali HB
                        'tanggal_transaksi' => $tgl_system,
                        'jenis_transaksi' => 'Bukti SYSTEM',
                        'keterangan_transaksi' => 'Setoran Angsuran WO An ' . $anggota->nama,
                        'debet' => 0,
                        'kredit' => $nominal,
                        'tanggal_posting' => $tgl_system,
                        'keterangan_posting' => '',
                        'id_admin' => $user_id,
                        'ip_address' => request()->ip(),
                    ],
                    [
                        'unit' => $unit,
                        'kode_transaksi' => $kodeTransaksi,
                        'kode_rekening' => '9910000', // Rekening Administratif - Rekening Lawan
                        'tanggal_transaksi' => $tgl_system,
                        'jenis_transaksi' => 'Bukti SYSTEM',
                        'keterangan_transaksi' => 'Setoran Angsuran WO An ' . $anggota->nama,
                        'debet' => $nominal,
                        'kredit' => '0',
                        'tanggal_posting' => $tgl_system,
                        'keterangan_posting' => '',
                        'id_admin' => $user_id,
                        'ip_address' => request()->ip(),
                    ],
                    [
                        'unit' => $unit,
                        'kode_transaksi' => $kodeTransaksi,
                        'kode_rekening' => '9141000', // Rekening Administratif - Piutang Murabahah
                        'tanggal_transaksi' => $tgl_system,
                        'jenis_transaksi' => 'Bukti SYSTEM',
                        'keterangan_transaksi' => 'Setoran Angsuran WO An ' . $anggota->nama,
                        'debet' => 0,
                        'kredit' => $nominal,
                        'tanggal_posting' => $tgl_system,
                        'keterangan_posting' => '',
                        'id_admin' => $user_id,
                        'ip_address' => request()->ip(),
                    ]
                ];

                DB::table('tabel_transaksi')->insert($transaksi_setor_angsuran_wo);

                DB::commit();

                return [
                    'status' => 200,
                    'body' => [
                        'success' => true,
                        'message' => 'Transaksi berhasil disimpan',
                        // 'data' => $transaksi
                    ],
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => 500,
                'body' => [
                    'success' => false,
                    'message' => 'Gagal menyimpan transaksi: ' . $e->getMessage(),
                    // 'trace' => $e->getTrace()
                ],
            ];
        }
    }
}
