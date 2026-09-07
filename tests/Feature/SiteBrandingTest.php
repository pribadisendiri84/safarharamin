<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\HajiExchangeRate;
use App\Support\SiteProfile;
use App\Support\WaMessages;
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

    public function test_whatsapp_float_message_can_be_customized_from_admin(): void
    {
        Setting::setValue('site_name', 'Arminareka Perdana');
        Setting::setValue(WaMessages::KEY_FLOAT, 'Assalamualaikum {site}, mau konsultasi umroh.');

        $this->get(route('go.whatsapp', ['from' => 'float']))
            ->assertRedirect('https://wa.me/6281234567890?text='.rawurlencode('Assalamualaikum Arminareka Perdana, mau konsultasi umroh.'));
    }

    public function test_whatsapp_header_message_can_be_customized_from_admin(): void
    {
        Setting::setValue('site_name', 'Arminareka Perdana');
        Setting::setValue(WaMessages::KEY_HEADER, 'Halo {site}, saya mau tanya paket dari header.');

        $this->get(route('go.whatsapp', ['from' => 'header']))
            ->assertRedirect('https://wa.me/6281234567890?text='.rawurlencode('Halo Arminareka Perdana, saya mau tanya paket dari header.'));
    }

    public function test_whatsapp_float_button_label_and_visibility_follow_settings(): void
    {
        Setting::setValue(WaMessages::KEY_FLOAT_LABEL, 'Chat Admin Sekarang');

        $this->get('/')
            ->assertOk()
            ->assertSee('Chat Admin Sekarang', false);

        Setting::setValue(WaMessages::KEY_FLOAT_ENABLED, '0');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Chat Admin Sekarang', false)
            ->assertDontSee('wa-float', false);
    }

    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'Arminareka Perdana',
            'site_tagline' => 'Katalog umroh.',
            'site_title_suffix' => 'Travel Haji & Umroh',
            'package_card_style' => SiteProfile::CARD_STYLE_CLASSIC,
            'wa_number' => '6281111111111',
            'wa_float_enabled' => '1',
            'wa_float_label' => WaMessages::DEFAULT_FLOAT_LABEL,
            'wa_msg_header' => WaMessages::DEFAULT_HEADER,
            'wa_msg_float' => WaMessages::DEFAULT_FLOAT,
            'wa_msg_package' => WaMessages::DEFAULT_PACKAGE,
            'wa_msg_register' => WaMessages::DEFAULT_REGISTER,
            'wa_msg_inquiry_reply' => WaMessages::DEFAULT_INQUIRY_REPLY,
            'haji_exchange_rate_enabled' => '1',
            'haji_exchange_rate_mode' => HajiExchangeRate::MODE_MANUAL,
            'haji_exchange_rate_currency' => 'SAR',
            'haji_exchange_rate' => 17735,
        ], $overrides);
    }

    public function test_admin_can_update_branding(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), $this->settingsPayload())
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('Arminareka Perdana', Setting::getValue('site_name'));
        $this->assertSame('6281111111111', Setting::getValue('wa_number'));
        $this->assertSame(SiteProfile::CARD_STYLE_CLASSIC, Setting::getValue('package_card_style'));

        $this->get('/')
            ->assertOk()
            ->assertSee('Arminareka Perdana')
            ->assertSee('Katalog umroh.');
    }

    public function test_admin_can_switch_package_card_style(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('Tampilan katalog')
            ->assertSee('Klasik')
            ->assertSee('Katalog');

        $this->actingAs($admin)
            ->put(route('admin.settings.update'), $this->settingsPayload([
                'package_card_style' => SiteProfile::CARD_STYLE_CATALOG,
            ]))
            ->assertRedirect(route('admin.settings.edit'));

        $this->assertSame(SiteProfile::CARD_STYLE_CATALOG, Setting::getValue('package_card_style'));
    }
}
