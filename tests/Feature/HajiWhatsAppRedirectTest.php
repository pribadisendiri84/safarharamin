<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Package;
use App\Models\VisitorEvent;
use App\Support\HajiPlusProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HajiWhatsAppRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function seedHajiPackage(): Package
    {
        return Package::query()->create([
            'title' => 'Haji Plus Contoh',
            'slug' => 'haji-plus-contoh',
            'type' => 'haji_plus',
            'departure_city' => 'jakarta',
            'duration_days' => 40,
            'price' => 60000000,
            'price_quad' => 60000000,
            'hotel_makkah' => 'Makkah Clock Tower',
            'hotel_madinah' => 'Madinah Pullman',
            'airline' => 'Saudia',
            'room_type' => 'quad',
            'seats_total' => 200,
            'seats_left' => 50,
            'status' => 'published',
            'images' => ['/images/placeholder-kaaba.svg'],
        ]);
    }

    public function test_haji_consult_whatsapp_click_creates_inquiry_and_redirects(): void
    {
        $package = $this->seedHajiPackage();

        $response = $this->from('/haji-khusus')
            ->get(route('go.haji.whatsapp', ['intent' => 'consult']));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/', $response->headers->get('Location'));

        $this->assertDatabaseHas('inquiries', [
            'kind' => 'tanya',
            'source' => Inquiry::SOURCE_WEBSITE,
            'name' => 'Pengunjung Haji',
            'package_id' => $package->id,
            'status' => Inquiry::STATUS_NEW,
        ]);

        $inquiry = Inquiry::query()->latest('id')->first();
        $this->assertStringContainsString('konsultasi umum', (string) $inquiry->notes);

        $this->assertDatabaseHas('visitor_events', [
            'type' => VisitorEvent::TYPE_WA_CLICK,
            'wa_placement' => 'haji',
        ]);
    }

    public function test_haji_room_whatsapp_click_records_room_in_notes(): void
    {
        $this->from('/haji-khusus')
            ->get(route('go.haji.whatsapp', ['intent' => 'room', 'room' => 'Quad']))
            ->assertRedirect();

        $inquiry = Inquiry::query()->latest('id')->first();
        $this->assertStringContainsString('Quad', (string) $inquiry->notes);
    }

    public function test_register_page_preselects_haji_package_from_query(): void
    {
        $package = $this->seedHajiPackage();

        $this->get(HajiPlusProgram::registerUrl('Triple'))
            ->assertOk()
            ->assertSee('Pendaftaran Haji Plus')
            ->assertSee('value="'.$package->id.'" selected', false)
            ->assertSee('Minat program Haji Plus — tipe kamar Triple.');
    }

    public function test_haji_page_links_to_register_and_tracked_whatsapp(): void
    {
        $this->get('/haji-khusus')
            ->assertOk()
            ->assertSee('/go/haji-wa?intent=consult', false)
            ->assertSee('/go/haji-wa?intent=room&amp;room=Quad', false)
            ->assertSee(HajiPlusProgram::registerUrl(), false)
            ->assertSee('Daftar');
    }
}
