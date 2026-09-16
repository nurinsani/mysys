<?php

namespace Tests\Feature\Realisasi;

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
class RealisasiMusyarokahTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::where('email', 'admin@ni')->first();
        $this->assertNotNull($admin, 'User admin@ni harus ada di database untuk test ini.');
        $this->actingAs($admin);
    }

    public function test_halaman_realisasi_musyarakah_bisa_diakses(): void
    {
        $response = $this->get('/realisasi-musyarakah');

        $response->assertOk();
        $response->assertViewIs('admin.realisasi_musyarokah.index');
    }

    public function test_proses_realisasi_berhasil(): void
    {
        $row = DB::table('temp_akad_mus')->first();
        $this->assertNotNull($row, 'Tidak ada data temp_akad_mus di database untuk dipakai sebagai sample test.');

        DB::table('pembiayaan')->where('cif', $row->cif)->update(['os' => 0]);

        $response = $this->postJson('/proses-realisasi-musyarakah', [
            'ids' => [$row->cif],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Musyarokah berhasil direalisasikan',
        ]);

        $this->assertDatabaseMissing('temp_akad_mus', ['cif' => $row->cif]);
    }

    public function test_proses_realisasi_gagal_validasi_ids_kosong(): void
    {
        $response = $this->postJson('/proses-realisasi-musyarakah', [
            'ids' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ids']);
    }
}
