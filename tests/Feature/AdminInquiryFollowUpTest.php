<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInquiryFollowUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_follow_up_note_moves_new_inquiry_to_contacted(): void
    {
        $admin = User::factory()->admin()->create();
        $inquiry = $this->makeInquiry();

        $this->actingAs($admin)
            ->post(route('admin.inquiries.notes.store', $inquiry), [
                'body' => 'Sudah WA, menunggu konfirmasi DP.',
            ])
            ->assertRedirect();

        $inquiry->refresh();
        $this->assertSame(Inquiry::STATUS_FOLLOWED_UP, $inquiry->status);
        $this->assertDatabaseHas('inquiry_follow_ups', [
            'inquiry_id' => $inquiry->id,
            'body' => 'Sudah WA, menunggu konfirmasi DP.',
            'user_id' => $admin->id,
        ]);
    }

    public function test_marking_inquiry_sold_records_sale_and_reduces_seats(): void
    {
        $admin = User::factory()->admin()->create();
        $inquiry = $this->makeInquiry(['pax' => 2]);
        $package = $inquiry->package;

        $this->actingAs($admin)
            ->put(route('admin.inquiries.update', $inquiry), [
                'status' => Inquiry::STATUS_SOLD,
                'package_id' => $package->id,
                'sold_pax' => 2,
            ])
            ->assertRedirect(route('admin.inquiries.show', $inquiry));

        $inquiry->refresh();
        $package->refresh();

        $this->assertTrue($inquiry->isSold());
        $this->assertSame(2, $inquiry->sold_pax);
        $this->assertSame(59_000_000, $inquiry->sold_amount);
        $this->assertTrue($inquiry->seats_applied);
        $this->assertSame(18, $package->seats_left);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('2')
            ->assertSee('closing tercatat');

        $this->actingAs($admin)
            ->get(route('admin.packages.index'))
            ->assertOk()
            ->assertSee('2 jamaah');
    }

    public function test_cancelling_a_sale_restores_seats(): void
    {
        $admin = User::factory()->admin()->create();
        $inquiry = $this->makeInquiry();

        $this->actingAs($admin)->put(route('admin.inquiries.update', $inquiry), [
            'status' => Inquiry::STATUS_SOLD,
            'package_id' => $inquiry->package_id,
            'sold_pax' => 2,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.inquiries.update', $inquiry), [
                'status' => Inquiry::STATUS_LOST,
            ])
            ->assertRedirect();

        $inquiry->refresh();
        $this->assertSame(Inquiry::STATUS_LOST, $inquiry->status);
        $this->assertFalse($inquiry->seats_applied);
        $this->assertSame(20, $inquiry->package->refresh()->seats_left);
    }

    public function test_sale_requires_a_package(): void
    {
        $admin = User::factory()->admin()->create();
        $inquiry = $this->makeInquiry(['package_id' => null]);

        $this->actingAs($admin)
            ->from(route('admin.inquiries.show', $inquiry))
            ->put(route('admin.inquiries.update', $inquiry), [
                'status' => Inquiry::STATUS_SOLD,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('package_id');

        $this->assertSame(Inquiry::STATUS_NEW, $inquiry->fresh()->status);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeInquiry(array $overrides = []): Inquiry
    {
        $package = Package::query()->create([
            'title' => 'Umroh Follow Up',
            'slug' => 'umroh-follow-up',
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

        return Inquiry::query()->create(array_merge([
            'kind' => 'daftar',
            'name' => 'Budi Jamaah',
            'phone' => '08123456789',
            'pax' => 2,
            'package_id' => $package->id,
            'status' => Inquiry::STATUS_NEW,
        ], $overrides));
    }
}
