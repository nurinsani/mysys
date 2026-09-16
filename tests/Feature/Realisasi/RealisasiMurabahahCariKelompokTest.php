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
class RealisasiMurabahahCariKelompokTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::where('email', 'admin@ni')->first();
        $this->assertNotNull($admin, 'User admin@ni harus ada di database untuk test ini.');
        $this->actingAs($admin);
    }

    public function test_halaman_realisasi_murabahah_bisa_diakses(): void
    {
        $response = $this->get(route('realisasi_murabahah'));

        $response->assertOk();
        $response->assertViewIs('admin.realisasi_murabahah.index');
    }

    public function test_cari_kelompok_berdasarkan_kode(): void
    {
        $kelompok = DB::table('kelompok')->first();
        $this->assertNotNull($kelompok, 'Tidak ada data kelompok di database untuk dipakai sebagai sample test.');

        $response = $this->getJson(route('realisasiMurabahah.cariKelompok', ['q' => $kelompok->code_kel]));

        $response->assertOk();
        $response->assertJsonFragment(['code_kel' => $kelompok->code_kel]);
    }

    public function test_cari_kelompok_berdasarkan_nama(): void
    {
        $kelompok = DB::table('kelompok')->whereNotNull('nama_kel')->first();
        $this->assertNotNull($kelompok, 'Tidak ada data kelompok dengan nama_kel di database untuk dipakai sebagai sample test.');

        $potonganNama = substr($kelompok->nama_kel, 0, 4);

        $response = $this->getJson(route('realisasiMurabahah.cariKelompok', ['q' => $potonganNama]));

        $response->assertOk();
        $response->assertJsonFragment(['code_kel' => $kelompok->code_kel]);
    }
}
