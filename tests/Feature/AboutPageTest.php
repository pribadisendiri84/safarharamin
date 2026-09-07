<?php

namespace Tests\Feature;

use App\Support\CompanyProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_renders_required_sections_and_compliance_copy(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertSee('About Arminareka', false)
            ->assertSee('youtube-nocookie.com/embed/k7tR0y4PFHY', false)
            ->assertSee('Tentang Arminareka', false)
            ->assertSee('Pengalaman', false)
            ->assertSee('Legalitas &amp; Perizinan', false)
            ->assertSee('Penghargaan &amp; Pencapaian', false)
            ->assertSee('Komitmen Pelayanan', false)
            ->assertDontSee('Kantor &amp; Kontak', false)
            ->assertSee(CompanyProfile::LEGAL_NAME, false)
            ->assertSee('arminarekaperdana.com/profil', false)
            ->assertSee('399 Tahun 2021', false)
            ->assertSee('MURI', false)
            ->assertSee('Garuda Indonesia', false)
            ->assertSee('Lihat Paket Umrah', false)
            ->assertSee('Hubungi Kami', false)
            ->assertDontSee('100% Terpercaya', false)
            ->assertDontSee('Terbesar di Indonesia', false)
            ->assertDontSee('No. 1 Indonesia', false);
    }

    public function test_about_page_has_seo_meta(): void
    {
        $response = $this->get('/about')->assertOk();

        $response->assertSee(
            'Mengenal Arminareka dan PT Arminareka Perdana, penyelenggara perjalanan ibadah Umrah dan Haji Khusus dengan pengalaman melayani jamaah sejak 1990.',
            false
        );
        $response->assertSee('<title>About Arminareka | PT Arminareka Perdana —', false);
    }
}
