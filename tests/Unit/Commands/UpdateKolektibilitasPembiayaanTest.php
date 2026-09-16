<?php

namespace Tests\Unit\Commands;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PENTING: sama seperti tests/Unit/Services/TransaksiServiceTest.php — tidak
 * ada database test terpisah, jadi test ini jalan terhadap database MySQL
 * development sungguhan. SENGAJA pakai DatabaseTransactions (bukan
 * RefreshDatabase) supaya tidak menjalankan migrasi ulang dan tiap test
 * di-rollback otomatis — command ini menulis ke tabel pembiayaan produksi.
 */
class UpdateKolektibilitasPembiayaanTest extends TestCase
{
    use DatabaseTransactions;

    private function buatPembiayaan(string $cif, int $os, string $golAwal): void
    {
        DB::table('pembiayaan')->insert([
            'buss_date' => now(),
            'code_kel' => 'TESTKEL',
            'no_anggota' => 'TEST-' . $cif,
            'cif' => $cif,
            'nama' => 'Test Kolektibilitas',
            'deal_type' => '1',
            'suffix' => '1',
            'bagi_hasil' => 0,
            'tenor' => 10,
            'plafond' => 500000,
            'os' => $os,
            'saldo_margin' => 0,
            'angsuran' => 50000,
            'pokok' => 50000,
            'ijaroh' => 0,
            'bulat' => 50000,
            'run_tenor' => '1',
            'ke' => '1',
            'usaha' => '1',
            'nama_usaha' => 'Test Usaha',
            'unit' => '001',
            'tgl_wakalah' => now(),
            'tgl_akad' => now(),
            'tgl_murab' => now(),
            'next_schedule' => now(),
            'maturity_date' => now(),
            'last_payment' => now(),
            'hari' => 'Senin',
            'cao' => 'TESTCAO',
            'status' => 'ANGGOTA',
            'status_usia' => 'NO',
            'status_app' => 'APPROVE',
            'gol' => $golAwal,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Insert $ft baris tunggakan (kredit > 0, tgl_tunggak beda-beda, tanpa
     * debet penutup) supaya ft = $ft persis — rumus ft yang sama seperti
     * ReportPpapController.
     */
    private function buatTunggakan(string $cif, int $ft): void
    {
        for ($i = 1; $i <= $ft; $i++) {
            DB::table('tunggakan')->insert([
                'tgl_tunggak' => now()->subDays($i)->format('Y-m-d'),
                'norek' => 'TEST',
                'unit' => '001',
                'cif' => $cif,
                'code_kel' => 'TESTKEL',
                'debet' => 0,
                'type' => '04',
                'kredit' => 10000,
                'userid' => 1,
                'ket' => 'Test tunggakan',
                'cao' => 'TESTCAO',
                'blok' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public static function kategoriProvider(): array
    {
        return [
            'ft 2 -> lancar (gol 1)' => [2, '1'],
            'ft 5 -> kurang lancar (gol 2)' => [5, '2'],
            'ft 9 -> diragukan (gol 3)' => [9, '3'],
            'ft 15 -> macet (gol 4)' => [15, '4'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('kategoriProvider')]
    public function test_kategori_gol_sesuai_ft(int $ft, string $golDiharapkan): void
    {
        $cif = 'K' . substr(uniqid(), -9);
        $this->buatPembiayaan($cif, 500000, '1');
        $this->buatTunggakan($cif, $ft);

        $this->artisan('pembiayaan:update-kolektibilitas')->assertExitCode(0);

        $this->assertSame($golDiharapkan, DB::table('pembiayaan')->where('cif', $cif)->value('gol'));
    }

    public function test_pembiayaan_tanpa_tunggakan_di_reset_ke_lancar(): void
    {
        $cif = 'K' . substr(uniqid(), -9);
        $this->buatPembiayaan($cif, 500000, '4');

        $this->artisan('pembiayaan:update-kolektibilitas')->assertExitCode(0);

        $this->assertSame('1', DB::table('pembiayaan')->where('cif', $cif)->value('gol'));
    }

    public function test_pembiayaan_lunas_tidak_ikut_diupdate(): void
    {
        $cif = 'K' . substr(uniqid(), -9);
        $this->buatPembiayaan($cif, 0, '4');

        $this->artisan('pembiayaan:update-kolektibilitas')->assertExitCode(0);

        $this->assertSame('4', DB::table('pembiayaan')->where('cif', $cif)->value('gol'));
    }
}
