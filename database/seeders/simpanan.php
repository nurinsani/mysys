<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class simpanan extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('simpanan')->insert([
            [
                'reff' => generate_reff('001'),
                'buss_date' => now()->subMonth(),
                'norek' => '00108612001',
                'unit' => '001',
                'cif' => '086120',
                'code_kel' => '003-0220',
                'debet' => 0,
                'type' => 'Deposit',
                'kredit' => 500000,
                'userid' => 'USR001',
                'ket' => 'Initial deposit',
                'cao' => '00303',
                'blok' => 0,
                'tgl_input' => now()->subMonth(),
                'kode_transaksi' => 'TRX001',
                'created_at' => now()->subMonth(),
                'updated_at' => now()->subMonth(),
            ],
            [
                'reff' => generate_reff('001'),
                'buss_date' => now(),
                'norek' => '00108612001',
                'unit' => '001',
                'cif' => '086120',
                'code_kel' => '003-0220',
                'debet' => 0,
                'type' => 'Deposit',
                'kredit' => 500000,
                'userid' => 'USR001',
                'ket' => 'Initial deposit',
                'cao' => '00303',
                'blok' => 0,
                'tgl_input' => now(),
                'kode_transaksi' => 'TRX001',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
