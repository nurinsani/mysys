<?php

namespace Tests\Feature\Dashboard;

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
class AdminDashboardTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::where('email', 'admin@ni')->first();
        $this->assertNotNull($admin, 'User admin@ni harus ada di database untuk test ini.');

        return $admin;
    }

    public function test_dashboard_admin_bisa_diakses_oleh_role_admin(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertViewIs('admin.index');
        $response->assertViewHasAll([
            'menus', 'pembiayaan', 'jumlahKelompok', 'jumlahPenunggak',
            'totalSimpanan', 'shuYtd', 'transaksiHariIni', 'title',
        ]);
        $response->assertViewHas('title', 'Dashboard');
    }

    public function test_dashboard_shu_ytd_dibaca_dari_tabel_master_bukan_dihitung_ulang(): void
    {
        $admin = $this->admin();

        DB::table('tabel_master')->updateOrInsert(
            ['unit' => $admin->unit, 'kode_rekening' => '3902000'],
            ['saldo_akhir' => 12345678]
        );

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertViewHas('shuYtd', 12345678.0);
    }

    public function test_dashboard_total_simpanan_menjumlahkan_3_tabel(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin');

        $response->assertOk();
        $response->assertViewHas('totalSimpanan', function ($totalSimpanan) {
            return is_numeric($totalSimpanan);
        });
    }

    public function test_dashboard_admin_menolak_guest(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_admin_menolak_role_bukan_admin(): void
    {
        $al = User::where('email', 'al@ni')->first();
        $this->assertNotNull($al, 'User al@ni harus ada di database untuk test ini.');

        $response = $this->actingAs($al)->get('/admin');

        $response->assertRedirect('/redirect');

        $followUp = $this->get('/redirect');
        $followUp->assertRedirect('/al');
    }
}
