<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Package;
use App\Models\Setting;
use App\Models\User;
use App\Models\VisitorEvent;
use App\Support\VisitorTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VisitorTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        VisitorTracker::flushReadyState();
        Setting::setValue('wa_number', '6281234567890');
    }

    public function test_public_page_records_a_page_view_and_admin_does_not(): void
    {
        $this->get('/?utm_source=ig&utm_medium=bio')
            ->assertOk();

        $this->assertDatabaseHas('visitor_events', [
            'type' => VisitorEvent::TYPE_PAGE_VIEW,
            'path' => '/',
            'utm_source' => 'ig',
            'utm_medium' => 'bio',
        ]);

        $before = VisitorEvent::query()->count();

        $this->get('/admin/login')->assertOk();
        $this->actingAs(User::factory()->superadmin()->create())
            ->get('/admin')
            ->assertOk();

        $this->assertSame($before, VisitorEvent::query()->count());
    }

    public function test_whatsapp_redirect_records_click_and_opens_wa(): void
    {
        $home = $this->get('/?utm_source=ig');
        $vid = $this->cookieValue($home, VisitorTracker::COOKIE_ID);
        $src = $this->cookieValue($home, VisitorTracker::COOKIE_SRC);

        $this->withUnencryptedCookie(VisitorTracker::COOKIE_ID, $vid)
            ->withUnencryptedCookie(VisitorTracker::COOKIE_SRC, $src)
            ->get(route('go.whatsapp', ['from' => 'header']))
            ->assertRedirect('https://wa.me/6281234567890?text='.rawurlencode('Halo SafarHaramin, saya ingin tanya paket haji/umroh.'));

        $this->assertDatabaseHas('visitor_events', [
            'type' => VisitorEvent::TYPE_WA_CLICK,
            'wa_placement' => 'header',
            'session_id' => $vid,
            'utm_source' => 'ig',
        ]);

        $this->withUnencryptedCookie(VisitorTracker::COOKIE_ID, $vid)
            ->withUnencryptedCookie(VisitorTracker::COOKIE_SRC, $src)
            ->get(route('go.whatsapp', ['from' => 'float']))
            ->assertRedirect('https://wa.me/6281234567890?text='.rawurlencode('Halo SafarHaramin, saya ingin tanya paket haji/umroh.'));
    }

    public function test_form_inquiry_still_creates_lead_and_form_wa_click_is_tracked(): void
    {
        Package::query()->create([
            'title' => 'Umroh Tracking',
            'slug' => 'umroh-tracking',
            'type' => 'umroh',
            'departure_city' => 'jakarta',
            'duration_days' => 9,
            'price' => 29500000,
            'hotel_stars' => 4,
            'room_type' => 'quad',
            'seats_total' => 20,
            'seats_left' => 20,
            'status' => 'published',
        ]);

        $this->post('/daftar', [
            'name' => 'Budi',
            'phone' => '08123456789',
            'city' => 'jakarta',
            'pax' => 2,
        ])->assertRedirect(route('register'));

        $this->assertDatabaseHas('inquiries', [
            'name' => 'Budi',
            'kind' => 'daftar',
            'source' => Inquiry::SOURCE_WEBSITE,
        ]);

        $this->get(route('go.whatsapp', ['from' => 'form']))
            ->assertRedirect();

        $this->assertDatabaseHas('visitor_events', [
            'type' => VisitorEvent::TYPE_WA_CLICK,
            'wa_placement' => 'form',
        ]);
    }

    public function test_only_superadmin_can_see_traffic(): void
    {
        $super = User::factory()->superadmin()->create();
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($super)
            ->get(route('admin.traffic.index'))
            ->assertOk()
            ->assertSee('Trafik website')
            ->assertSee('Klik WhatsApp');

        $this->actingAs($super)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Lihat trafik lengkap');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Funnel website vs tim')
            ->assertDontSee('Lihat trafik lengkap');

        $this->actingAs($admin)
            ->get(route('admin.traffic.index'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($staff)
            ->get(route('admin.traffic.index'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($staff)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Lihat trafik lengkap')
            ->assertDontSee('Funnel website vs tim');
    }

    public function test_bots_are_not_recorded(): void
    {
        $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])
            ->get('/')
            ->assertOk();

        $this->assertDatabaseMissing('visitor_events', ['type' => VisitorEvent::TYPE_PAGE_VIEW]);
    }

    public function test_bot_whatsapp_clicks_are_not_recorded(): void
    {
        $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])
            ->get(route('go.whatsapp', ['from' => 'header']))
            ->assertRedirect();

        $this->assertDatabaseMissing('visitor_events', ['type' => VisitorEvent::TYPE_WA_CLICK]);
    }

    public function test_admin_dashboard_works_without_visitor_events_table(): void
    {
        Schema::dropIfExists('visitor_events');
        VisitorTracker::flushReadyState();

        $this->actingAs(User::factory()->superadmin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Lihat trafik lengkap');

        $this->actingAs(User::factory()->superadmin()->create())
            ->get(route('admin.traffic.index'))
            ->assertOk();
    }

    private function cookieValue($response, string $name): string
    {
        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                return (string) $cookie->getValue();
            }
        }

        $this->fail('Cookie '.$name.' was not set.');
    }
}
