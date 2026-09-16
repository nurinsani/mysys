<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\Setoran\SetoranPerkelompokService;
use App\Services\Transaksi\TransaksiService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Sesi 1.7 (rencana_pengerjaan.md) — pastikan setiap insert manual ke
 * tabel_transaksi mengisi ip_address, bukan cuma id_admin. Sama seperti
 * TransaksiServiceTest — jalan terhadap database MySQL development
 * sungguhan, dibungkus DatabaseTransactions supaya aman di-rollback.
 */
class AuditTrailIpAddressTest extends TestCase
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
     * PENTING: tabel_transaksi punya 3,6 juta+ baris — assertDatabaseHas()
     * generik SANGAT lambat di sini (kalau assertion gagal cocok, Laravel
     * full-scan seluruh tabel untuk mencari "hasil mirip" buat pesan error,
     * bisa 60-120 detik). Query manual `ORDER BY id_transaksi DESC LIMIT 1`
     * pakai index primary key, jadi tetap cepat meski tabelnya besar.
     */
    public function test_transaksi_service_mengisi_ip_address_di_tabel_transaksi(): void
    {
        $cif = DB::table('anggota')->value('cif');
        $this->assertNotNull($cif);

        $result = app(TransaksiService::class)->simpanTransaksi([
            'cif' => $cif,
            'nominal' => 10000,
            'jenis_transaksi' => 2,
            'keterangan' => 'Test audit trail ip_address',
        ]);

        $this->assertSame(200, $result['status']);

        $row = DB::table('tabel_transaksi')
            ->where('kode_rekening', '1120000')
            ->orderByDesc('id_transaksi')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('127.0.0.1', $row->ip_address);
    }

    public function test_setoran_perkelompok_service_mengisi_ip_address_di_tabel_transaksi(): void
    {
        $row = DB::table('pembiayaan')
            ->join('anggota', 'pembiayaan.no_anggota', '=', 'anggota.no')
            ->whereColumn('pembiayaan.run_tenor', '<', 'pembiayaan.tenor')
            ->select('pembiayaan.code_kel', 'pembiayaan.no_anggota', 'pembiayaan.bulat')
            ->first();
        $this->assertNotNull($row, 'Tidak ada pembiayaan aktif di database untuk dipakai sebagai sample test.');

        $result = app(SetoranPerkelompokService::class)->prosesSetoran(
            $row->code_kel,
            [$row->no_anggota],
            [$row->no_anggota => $row->bulat],
            [$row->no_anggota => 1]
        );

        $this->assertSame(200, $result['status']);

        $tabelTransaksi = DB::table('tabel_transaksi')
            ->where('kode_rekening', '1413000')
            ->orderByDesc('id_transaksi')
            ->first();

        $this->assertNotNull($tabelTransaksi);
        $this->assertSame('127.0.0.1', $tabelTransaksi->ip_address);
    }
}
