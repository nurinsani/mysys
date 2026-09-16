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
class InputTransaksiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::where('email', 'admin@ni')->first();
        $this->assertNotNull($admin, 'User admin@ni harus ada di database untuk test ini.');
        $this->actingAs($admin);
    }

    private function ambilCifSample(): string
    {
        $cif = DB::table('anggota')->value('cif');
        $this->assertNotNull($cif, 'Tidak ada data anggota di database untuk dipakai sebagai sample test.');

        return $cif;
    }

    public function test_halaman_input_transaksi_bisa_diakses(): void
    {
        $response = $this->get('/transaksi/input-transaksi');

        $response->assertOk();
        $response->assertViewIs('admin.input_transaksi.index');
    }

    public function test_get_by_cif_ditemukan(): void
    {
        $cif = $this->ambilCifSample();

        $response = $this->get("/transaksi/input-transaksi/get-cif/{$cif}");

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'data' => ['nama']]);
    }

    public function test_get_by_cif_tidak_ditemukan(): void
    {
        $response = $this->get('/transaksi/input-transaksi/get-cif/CIF-TIDAK-ADA-999999');

        $response->assertStatus(404);
        $response->assertJson(['success' => false]);
    }

    public function test_store_penarikan_tunai_berhasil(): void
    {
        $response = $this->postJson('/transaksi/input-transaksi', [
            'cif' => $this->ambilCifSample(),
            'nominal' => 10000,
            'jenis_transaksi' => 2,
            'keterangan' => 'Test feature penarikan',
            'jenis_pemindahan' => 'debet',
            'jenis_simpanan' => 'pokok',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_store_gagal_validasi_cif_tidak_ada(): void
    {
        $response = $this->postJson('/transaksi/input-transaksi', [
            'cif' => 'CIF-TIDAK-ADA-999999',
            'nominal' => 10000,
            'jenis_transaksi' => 2,
            'jenis_pemindahan' => 'debet',
            'jenis_simpanan' => 'pokok',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cif']);
    }

    public function test_get_history_berhasil(): void
    {
        $cif = $this->ambilCifSample();

        $response = $this->get("/transaksi/input-transaksi/history/{$cif}");

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_penarikan_tunai_muncul_di_history(): void
    {
        $cif = $this->ambilCifSample();

        $store = $this->postJson('/transaksi/input-transaksi', [
            'cif' => $cif,
            'nominal' => 12345,
            'jenis_transaksi' => 2,
            'keterangan' => 'Test history penarikan',
            'jenis_pemindahan' => 'debet',
            'jenis_simpanan' => 'pokok',
        ]);
        $store->assertOk();
        $store->assertJson(['success' => true]);

        $response = $this->getJson("/transaksi/input-transaksi/history/{$cif}");

        $response->assertOk();
        $response->assertJsonFragment(['ket' => 'Test history penarikan', 'jenis' => 'Simpanan']);
    }
}
