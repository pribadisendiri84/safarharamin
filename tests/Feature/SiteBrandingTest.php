<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_uses_site_name_from_settings(): void
    {
        Setting::setValue('site_name', 'Arminareka Perdana');
        Setting::setValue('site_tagline', 'Travel umroh Arminareka.');
        Setting::setValue('site_title_suffix', 'Haji & Umroh');

        $this->get('/')
            ->assertOk()
            ->assertSee('Arminareka Perdana')
            ->assertSee('Travel umroh Arminareka.')
            ->assertDontSee('SafarHaramin');

        $this->get(route('go.whatsapp', ['from' => 'float']))
            ->assertRedirect('https://wa.me/6281234567890?text='.rawurlencode('Halo Arminareka Perdana, saya ingin tanya paket haji/umroh.'));
    }

    public function test_admin_can_update_branding(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), [
                'site_name' => 'Arminareka Perdana',
                'site_tagline' => 'Katalog umroh.',
                'site_title_suffix' => 'Travel Haji & Umroh',
                'wa_number' => '6281111111111',
            ])
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('Arminareka Perdana', Setting::getValue('site_name'));
        $this->assertSame('6281111111111', Setting::getValue('wa_number'));

        $this->get('/')
            ->assertOk()
            ->assertSee('Arminareka Perdana')
            ->assertSee('Katalog umroh.');
    }
}
