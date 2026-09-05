<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminCaptcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_and_captcha_image_are_available(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('name="captcha"', false)
            ->assertSee(route('admin.captcha'), false);

        $this->get(route('admin.captcha'))
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertSessionHas(AdminCaptcha::HASH_KEY)
            ->assertSessionHas(AdminCaptcha::EXPIRES_KEY);
    }

    public function test_valid_captcha_and_password_login_successfully(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin-login@example.test',
            'password' => 'password123',
        ]);

        $this->withSession($this->validCaptchaSession())
            ->post(route('admin.login.store'), [
                'email' => 'admin-login@example.test',
                'password' => 'password123',
                'captcha' => 'abc123',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_wrong_or_expired_captcha_does_not_increment_login_failures(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'captcha@example.test',
            'password' => 'password123',
        ]);

        $this->withSession($this->validCaptchaSession('ABC123'))
            ->post(route('admin.login.store'), [
                'email' => $user->email,
                'password' => 'wrong-password',
                'captcha' => 'ZZZ999',
            ])
            ->assertSessionHasErrors('captcha');

        $this->withSession([
            AdminCaptcha::HASH_KEY => hash('sha256', 'ABC123'),
            AdminCaptcha::EXPIRES_KEY => now()->subMinute()->timestamp,
        ])->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
            'captcha' => 'ABC123',
        ])->assertSessionHasErrors('captcha');

        $this->assertSame(0, $user->fresh()->login_failed_attempts);
        $this->assertNull($user->fresh()->login_locked_at);
    }

    public function test_three_wrong_passwords_lock_account_globally_until_unlocked(): void
    {
        $user = User::factory()->admin()->create([
            'email' => 'locked@example.test',
            'password' => 'password123',
        ]);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->withSession($this->validCaptchaSession())
                ->post(route('admin.login.store'), [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                    'captcha' => 'ABC123',
                ])
                ->assertSessionHasErrors('email');
        }

        $user->refresh();
        $this->assertSame(3, $user->login_failed_attempts);
        $this->assertTrue($user->isLoginLocked());

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->withSession($this->validCaptchaSession())
            ->post(route('admin.login.store'), [
                'email' => $user->email,
                'password' => 'password123',
                'captcha' => 'ABC123',
            ])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_superadmin_and_cli_can_unlock_account(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $locked = User::factory()->admin()->create(['email' => 'unlock@example.test']);
        $locked->forceFill(['login_failed_attempts' => 3, 'login_locked_at' => now()])->saveQuietly();

        $this->actingAs($superadmin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Terkunci')
            ->assertSee('Buka kunci');

        $this->actingAs($superadmin)
            ->post(route('admin.users.unlock', $locked))
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($locked->fresh()->isLoginLocked());

        $locked->forceFill(['login_failed_attempts' => 3, 'login_locked_at' => now()])->saveQuietly();

        $this->artisan('admin:unlock', ['email' => $locked->email])
            ->expectsOutputToContain('berhasil dibuka')
            ->assertSuccessful();

        $this->assertFalse($locked->fresh()->isLoginLocked());
        $this->assertSame(0, $locked->fresh()->login_failed_attempts);
    }
}
