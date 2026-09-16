<?php

namespace Tests\Feature\Transaksi;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PENTING: sama seperti tests/Unit/Services/TransaksiServiceTest.php — tidak
 * ada database test terpisah, jadi test ini jalan terhadap database MySQL
 * development sungguhan. SENGAJA pakai DatabaseTransactions (bukan
 * RefreshDatabase) supaya tidak menjalankan migrasi ulang (skema live sudah
 * menyimpang dari file migration di beberapa tempat) dan tiap test
 * di-rollback otomatis.
 */
class SetoranPerkelompokTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::where('email', 'admin@ni')->first();
        $this->assertNotNull($admin, 'User admin@ni harus ada di database untuk test ini.');
        $this->actingAs($admin);
    }

    private function ambilKelompokDenganPembiayaanAktif(): array
    {
        $row = DB::table('pembiayaan')
            ->join('anggota', 'pembiayaan.no_anggota', '=', 'anggota.no')
            ->whereColumn('pembiayaan.run_tenor', '<', 'pembiayaan.tenor')
            ->select('pembiayaan.code_kel', 'pembiayaan.no_anggota', 'pembiayaan.bulat')
            ->first();

        $this->assertNotNull($row, 'Tidak ada pembiayaan aktif di database untuk dipakai sebagai sample test.');

        return [$row->code_kel, $row->no_anggota, (float) $row->bulat];
    }

    public function test_halaman_setoran_perkelompok_bisa_diakses(): void
    {
        $response = $this->get('/transaksi/setoran-perkelompok');

        $response->assertOk();
        $response->assertViewIs('admin.setoran_perkelompok.index');
    }

    public function test_filter_kelompok_ditemukan(): void
    {
        [$codeKel] = $this->ambilKelompokDenganPembiayaanAktif();

        $response = $this->postJson('/transaksi/setoran-perkelompok/filter', [
            'code_kel' => $codeKel,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['kelompok', 'anggota']);
    }

    public function test_filter_kelompok_tidak_ditemukan(): void
    {
        $response = $this->postJson('/transaksi/setoran-perkelompok/filter', [
            'code_kel' => 'KODE-KELOMPOK-TIDAK-ADA',
        ]);

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Kamu belum pilih kelompok']);
    }

    public function test_proses_setoran_berhasil(): void
    {
        [$codeKel, $noAnggota, $bulat] = $this->ambilKelompokDenganPembiayaanAktif();

        $response = $this->postJson("/transaksi/setoran-perkelompok/proses/{$codeKel}", [
            'pilih_anggota' => [$noAnggota],
            'input_nyata_setor' => [$noAnggota => $bulat],
            'input_debet' => [$noAnggota => 1],
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_proses_setoran_tidak_ada_anggota_dipilih(): void
    {
        [$codeKel] = $this->ambilKelompokDenganPembiayaanAktif();

        $response = $this->postJson("/transaksi/setoran-perkelompok/proses/{$codeKel}", [
            'pilih_anggota' => [],
            'input_nyata_setor' => [],
            'input_debet' => [],
        ]);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Tidak ada anggota yang dipilih']);
    }
}
