<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);

        Route::get('/_test/error/{status}', function (int $status) {
            if ($status === 500) {
                throw new \RuntimeException('database password must never be shown');
            }

            abort($status);
        });
        Route::get('/_test/post-too-large', fn () => throw new PostTooLargeException);
    }

    public function test_main_http_errors_use_professional_branded_pages(): void
    {
        $expectations = [
            401 => 'Silakan masuk terlebih dahulu',
            403 => 'Akses tidak diizinkan',
            404 => 'Halaman tidak ditemukan',
            419 => 'Sesi halaman sudah berakhir',
            429 => 'Terlalu banyak permintaan',
            500 => 'Terjadi kesalahan pada sistem',
            503 => 'Layanan sedang tidak tersedia',
        ];

        foreach ($expectations as $status => $copy) {
            $this->get('/_test/error/'.$status)
                ->assertStatus($status)
                ->assertSee('SafarHaramin')
                ->assertSee($copy)
                ->assertSee('Kembali / tutup')
                ->assertDontSee('database password must never be shown');
        }
    }

    public function test_laravel_post_too_large_error_uses_branded_413_page(): void
    {
        $this->get('/_test/post-too-large')
            ->assertStatus(413)
            ->assertSee('File atau data terlalu besar')
            ->assertSee('maksimal 5 MB');
    }

    public function test_json_errors_remain_json_and_do_not_render_html_page(): void
    {
        $this->getJson('/_test/error/404')
            ->assertNotFound()
            ->assertJsonStructure(['message'])
            ->assertDontSee('Kembali / tutup');
    }

    public function test_admin_error_page_returns_authenticated_user_to_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/alamat-tidak-ada')
            ->assertNotFound()
            ->assertSee('Ke dashboard')
            ->assertSee(route('admin.dashboard'), false);
    }

    public function test_static_server_fallback_pages_exist(): void
    {
        $this->assertFileExists(public_path('errors/413.html'));
        $this->assertFileExists(public_path('errors/50x.html'));
        $this->assertStringContainsString('File atau data terlalu besar', file_get_contents(public_path('errors/413.html')));
    }

    public function test_form_and_upload_validation_return_to_branded_feedback_modal(): void
    {
        $this->followingRedirects()
            ->post(route('register.store'), [
                'name' => '',
                'phone' => '',
                'pax' => 0,
            ])
            ->assertOk()
            ->assertSee('data-feedback-modal', false)
            ->assertSee('Perlu diperiksa');

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->followingRedirects()
            ->post(route('admin.packages.import.store'), [
                'csv' => UploadedFile::fake()->create('too-large.csv', 3000, 'text/csv'),
            ])
            ->assertOk()
            ->assertSee('data-feedback-modal', false);
    }
}
