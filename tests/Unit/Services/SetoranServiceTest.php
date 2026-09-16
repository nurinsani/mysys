<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\Setoran\SetoranBedaHariService;
use App\Services\Setoran\SetoranPerkelompokService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sama seperti TransaksiServiceTest — jalan terhadap database MySQL
 * development sungguhan, dibungkus DatabaseTransactions (bukan
 * RefreshDatabase) supaya aman di-rollback otomatis.
 */
class SetoranServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::where('email', 'admin@ni')->first();
        $this->assertNotNull($admin, 'User admin@ni harus ada di database untuk test ini.');
        $this->actingAs($admin);
    }

    /**
     * Cari code_kel yang punya pembiayaan aktif (run_tenor < tenor) supaya
     * jalur sukses beneran teruji, bukan cuma jalur "tidak ada data".
     */
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

    public function test_setoran_perkelompok_berhasil_untuk_kelompok_dengan_pembiayaan_aktif(): void
    {
        [$codeKel, $noAnggota, $bulat] = $this->ambilKelompokDenganPembiayaanAktif();

        $result = app(SetoranPerkelompokService::class)->prosesSetoran(
            $codeKel,
            [$noAnggota],
            [$noAnggota => $bulat],
            [$noAnggota => 1]
        );

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
    }

    public function test_setoran_beda_hari_berhasil_untuk_kelompok_dengan_pembiayaan_aktif(): void
    {
        [$codeKel, $noAnggota, $bulat] = $this->ambilKelompokDenganPembiayaanAktif();

        $result = app(SetoranBedaHariService::class)->prosesSetoran(
            $codeKel,
            [$noAnggota],
            [$noAnggota => $bulat],
            [$noAnggota => 1]
        );

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
    }

    public function test_setoran_perkelompok_kelompok_tidak_ditemukan(): void
    {
        $result = app(SetoranPerkelompokService::class)->prosesSetoran(
            'KODE-KELOMPOK-TIDAK-ADA',
            ['x'],
            [],
            []
        );

        $this->assertSame(404, $result['status']);
        $this->assertSame('Kelompok tidak ditemukan', $result['body']['message']);
    }

    public function test_setoran_perkelompok_tidak_ada_anggota_dipilih(): void
    {
        [$codeKel] = $this->ambilKelompokDenganPembiayaanAktif();

        $result = app(SetoranPerkelompokService::class)->prosesSetoran($codeKel, [], [], []);

        $this->assertSame(400, $result['status']);
        $this->assertSame('Tidak ada anggota yang dipilih', $result['body']['message']);
    }
}
