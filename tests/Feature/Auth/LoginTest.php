<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * PENTING: sama seperti tests/Unit/Services/TransaksiServiceTest.php — tidak
 * ada database test terpisah, jadi test ini jalan terhadap database MySQL
 * development sungguhan. SENGAJA pakai DatabaseTransactions (bukan
 * RefreshDatabase) supaya tidak menjalankan migrasi ulang (skema live sudah
 * menyimpang dari file migration di beberapa tempat) dan tiap test
 * di-rollback otomatis.
 */
class LoginTest extends TestCase
{
    use DatabaseTransactions;

    public function test_halaman_login_bisa_diakses_tanpa_login(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    }

    public static function roleRedirectProvider(): array
    {
        return [
            'admin (role 1) ke /admin' => ['admin@ni', '/admin'],
            'AL (role 2) ke /al' => ['al@ni', '/al'],
            'AH (role 3) ke /ah' => ['ah@ni', '/ah'],
            'KP (role 4) ke /kp' => ['kp@ni', '/kp'],
        ];
    }

    #[DataProvider('roleRedirectProvider')]
    public function test_login_berhasil_redirect_sesuai_role(string $email, string $expectedPath): void
    {
        $user = User::where('email', $email)->first();
        $this->assertNotNull($user, "User $email harus ada di database untuk test ini.");

        $password = 'test-password-' . uniqid();
        $user->forceFill(['password' => Hash::make($password)])->save();

        $response = $this->post('/', [
            'email' => $email,
            'password' => $password,
        ]);

        $response->assertRedirect($expectedPath);
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_gagal_dengan_password_salah(): void
    {
        $admin = User::where('email', 'admin@ni')->first();
        $this->assertNotNull($admin, 'User admin@ni harus ada di database untuk test ini.');

        $response = $this->post('/', [
            'email' => $admin->email,
            'password' => 'password-yang-pasti-salah',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('error', 'Email atau password salah');
        $this->assertGuest();
    }

    public function test_login_gagal_dengan_email_tidak_terdaftar(): void
    {
        $response = $this->post('/', [
            'email' => 'tidak-ada@example.test',
            'password' => 'apapun',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHas('error', 'Email atau password salah');
        $this->assertGuest();
    }

    public function test_logout_berhasil(): void
    {
        $admin = User::where('email', 'admin@ni')->first();
        $this->assertNotNull($admin, 'User admin@ni harus ada di database untuk test ini.');

        $response = $this->actingAs($admin)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
