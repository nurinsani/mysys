<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\Transaksi\TransaksiService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * PENTING: aplikasi ini belum punya database test terpisah (tidak ada
 * .env.testing, sqlite dikomentari di phpunit.xml) — test ini jalan
 * terhadap database MySQL development yang sungguhan. SENGAJA pakai
 * DatabaseTransactions (bukan RefreshDatabase) supaya:
 * - Tidak pernah menjalankan migrasi ulang (skema tabel live sudah
 *   menyimpang dari file migration di beberapa tempat — lihat catatan di
 *   rencana_pengerjaan.md Sesi 2.2/2.4/2.5).
 * - Setiap test method dibungkus transaksi & di-rollback otomatis, tidak
 *   ada data yang tertinggal permanen.
 */
class TransaksiServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected TransaksiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TransaksiService::class);

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

    public function test_setoran_pertama_membuat_simpanan_pokok_dan_wajib(): void
    {
        $cif = $this->ambilCifSample();
        DB::table('simpanan_pokok')->where('cif', $cif)->delete();

        $result = $this->service->simpanTransaksi([
            'cif' => $cif,
            'nominal' => 60000,
            'jenis_transaksi' => 1,
            'keterangan' => 'Test setoran pertama',
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
        $this->assertNotNull($result['body']['data']['pokok']);
        $this->assertNotNull($result['body']['data']['wajib']);
    }

    public function test_setoran_setelah_punya_pokok_hanya_membuat_simpanan_wajib(): void
    {
        $cif = $this->ambilCifSample();
        if (!DB::table('simpanan_pokok')->where('cif', $cif)->exists()) {
            DB::table('simpanan_pokok')->insert([
                'buss_date' => now(), 'norek' => 'TEST', 'unit' => '001', 'cif' => $cif,
                'code_kel' => 'TEST', 'debet' => 0, 'type' => '01', 'kredit' => 50000,
                'userid' => 1, 'ket' => 'seed test', 'cao' => 'TEST', 'blok' => '1',
                'tgl_input' => now(), 'kode_transaksi' => 'TEST-SEED',
            ]);
        }

        $result = $this->service->simpanTransaksi([
            'cif' => $cif,
            'nominal' => 25000,
            'jenis_transaksi' => 1,
            'keterangan' => 'Test setoran sudah punya pokok',
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
        $this->assertSame('Transaksi simpanan wajib berhasil disimpan', $result['body']['message']);
    }

    public function test_penarikan_tunai_berhasil(): void
    {
        $result = $this->service->simpanTransaksi([
            'cif' => $this->ambilCifSample(),
            'nominal' => 10000,
            'jenis_transaksi' => 2,
            'keterangan' => 'Test penarikan',
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
    }

    public function test_setor_angsuran_berhasil(): void
    {
        $result = $this->service->simpanTransaksi([
            'cif' => $this->ambilCifSample(),
            'nominal' => 15000,
            'jenis_transaksi' => 3,
            'keterangan' => 'Test setor angsuran',
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
    }

    public static function pemindahbukuanProvider(): array
    {
        return [
            'debet pokok' => ['debet', 'pokok'],
            'debet wajib' => ['debet', 'wajib'],
            'kredit pokok' => ['kredit', 'pokok'],
            'kredit wajib' => ['kredit', 'wajib'],
        ];
    }

    #[DataProvider('pemindahbukuanProvider')]
    public function test_pemindahbukuan_berhasil(string $jenisPemindahan, string $jenisSimpanan): void
    {
        $result = $this->service->simpanTransaksi([
            'cif' => $this->ambilCifSample(),
            'nominal' => 20000,
            'jenis_transaksi' => 4,
            'keterangan' => 'Test pemindahbukuan',
            'jenis_pemindahan' => $jenisPemindahan,
            'jenis_simpanan' => $jenisSimpanan,
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
    }

    public function test_setoran_angsuran_wo_berhasil(): void
    {
        $result = $this->service->simpanTransaksi([
            'cif' => $this->ambilCifSample(),
            'nominal' => 30000,
            'jenis_transaksi' => 5,
            'keterangan' => 'Test setoran WO',
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertTrue($result['body']['success']);
    }

    public function test_cif_tidak_ditemukan_mengembalikan_gagal(): void
    {
        $result = $this->service->simpanTransaksi([
            'cif' => 'CIF-TIDAK-ADA-999999',
            'nominal' => 10000,
            'jenis_transaksi' => 2,
            'keterangan' => 'Test cif invalid',
        ]);

        $this->assertSame(500, $result['status']);
        $this->assertFalse($result['body']['success']);
    }
}
