<?php

namespace Tests\Feature\ActivityLog;

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
class UserAccessLogTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_tercatat_di_activity_log(): void
    {
        $admin = User::where('email', 'admin@ni')->first();
        $this->assertNotNull($admin, 'User admin@ni harus ada di database untuk test ini.');

        $password = 'test-password-' . uniqid();
        $admin->forceFill(['password' => \Illuminate\Support\Facades\Hash::make($password)])->save();

        $this->post('/', ['email' => $admin->email, 'password' => $password]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'akses',
            'event' => 'login',
            'causer_id' => $admin->id,
        ]);
    }

    public function test_request_terautentikasi_tercatat_sebagai_akses(): void
    {
        $admin = User::where('email', 'admin@ni')->first();
        $this->actingAs($admin)->get('/admin');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'akses',
            'event' => 'akses',
            'causer_id' => $admin->id,
            'description' => 'GET admin',
        ]);
    }

    public function test_logout_tercatat_di_activity_log(): void
    {
        $admin = User::where('email', 'admin@ni')->first();
        $this->actingAs($admin)->post('/logout');

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'akses',
            'event' => 'logout',
            'causer_id' => $admin->id,
        ]);
    }

    public function test_halaman_activity_log_bisa_difilter_per_event(): void
    {
        $admin = User::where('email', 'admin@ni')->first();
        $this->actingAs($admin)->get('/admin');

        $response = $this->actingAs($admin)->get('/admin/activity-log?' . http_build_query(['log_name' => 'akses', 'event' => 'akses']));

        $response->assertOk();
        $response->assertViewHas('logs');
        foreach ($response->viewData('logs') as $log) {
            $this->assertSame('akses', $log->log_name);
            $this->assertSame('akses', $log->event);
        }
    }

    public function test_request_tanpa_login_tidak_tercatat(): void
    {
        DB::table('activity_log')->where('log_name', 'akses')->delete();

        $this->get('/');

        $this->assertDatabaseMissing('activity_log', ['log_name' => 'akses']);
    }
}
