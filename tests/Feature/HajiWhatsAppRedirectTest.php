<?php

namespace Tests\Feature;

use App\Models\Departure;
use App\Models\Inquiry;
use App\Models\User;
use App\Models\VisitorEvent;
use App\Support\HajiPlusPage;
use App\Support\HajiPlusProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HajiWhatsAppRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_haji_consult_whatsapp_click_creates_inquiry_and_redirects(): void
    {
        $response = $this->from('/haji-khusus')
            ->get(route('go.haji.whatsapp', ['intent' => 'consult']));

        $response->assertRedirect();
        $this->assertStringStartsWith('https://wa.me/', $response->headers->get('Location'));

        $this->assertDatabaseHas('inquiries', [
            'kind' => 'tanya',
            'source' => Inquiry::SOURCE_WEBSITE,
            'program_kind' => 'haji',
            'name' => 'Pengunjung Haji',
            'package_id' => null,
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
        $this->assertSame('haji', $inquiry->program_kind);
        $this->assertStringContainsString('Quad', (string) $inquiry->notes);
    }

    public function test_register_page_for_haji_program_has_no_package_picker(): void
    {
        $this->get(HajiPlusProgram::registerUrl('Triple'))
            ->assertOk()
            ->assertSee('Pendaftaran Haji Plus')
            ->assertSee('name="program_kind" value="haji"', false)
            ->assertDontSee('name="package_id"', false)
            ->assertSee('Minat program Haji Plus — tipe kamar Triple.');
    }

    public function test_haji_page_links_to_register_and_tracked_whatsapp(): void
    {
        $this->get('/haji-khusus')
            ->assertOk()
            ->assertSee('/go/haji-wa?intent=consult', false)
            ->assertSee(HajiPlusProgram::registerUrl(), false)
            ->assertSee('Daftar');
    }
}
