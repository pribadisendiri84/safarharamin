<?php

namespace Tests\Feature;

use App\Models\Departure;
use App\Models\Pilgrim;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_operations_dashboard_is_accessible(): void
    {
        $this->seed(\Database\Seeders\OperationsSeeder::class);

        $this->actingAs($this->admin())
            ->get(route('admin.operations.dashboard'))
            ->assertOk()
            ->assertSee('Operasi Jamaah')
            ->assertSee('Lunas')
            ->assertSee('Cicilan')
            ->assertSee('Belum bayar');
    }

    public function test_operations_dashboard_lists_incomplete_rooms(): void
    {
        $this->seed(\Database\Seeders\OperationsSeeder::class);

        $this->actingAs($this->admin())
            ->get(route('admin.operations.dashboard'))
            ->assertOk()
            ->assertSee('Detail room belum penuh')
            ->assertSee('Q-02')
            ->assertSee('1 / 4');
    }

    public function test_departures_and_pilgrims_crud_flow(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.operations.departures.store'), [
                'program_name' => 'Umroh April 2026',
                'program_kind' => 'umroh',
                'departure_date' => '2026-04-12',
                'airline' => 'Garuda',
                'flight_number' => 'GA-980',
                'hotel_makkah' => 'Swissotel',
                'hotel_madinah' => 'Anwar Al Madinah',
            ])
            ->assertRedirect(route('admin.operations.departures.index'));

        $departure = Departure::query()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.operations.pilgrims.store'), [
                'departure_id' => $departure->id,
                'full_name' => 'Abdul Hadi',
                'phone' => '081234567890',
                'gender' => 'male',
                'room_type' => 'quad',
                'package_price' => 25000000,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('pilgrims', [
            'full_name' => 'Abdul Hadi',
            'departure_id' => $departure->id,
            'room_type' => 'quad',
        ]);
    }

    public function test_auto_grouping_respects_room_capacity(): void
    {
        $admin = $this->admin();
        $departure = Departure::query()->create([
            'program_name' => 'Umroh Grouping Test',
            'program_kind' => 'umroh',
        ]);

        foreach (['Abdul', 'Nur', 'Ferio', 'Salsa', 'Tita', 'Syah', 'Sarah', 'Imam', 'Bimo', 'Rina'] as $index => $name) {
            Pilgrim::query()->create([
                'departure_id' => $departure->id,
                'full_name' => $name,
                'room_type' => $index < 7 ? 'quad' : 'triple',
            ]);
        }

        $this->actingAs($admin)
            ->post(route('admin.operations.grouping.auto', $departure))
            ->assertRedirect();

        $quadRooms = Room::query()->where('departure_id', $departure->id)->where('room_type', 'quad')->withCount('pilgrims')->get();
        $this->assertSame(2, $quadRooms->count());
        $this->assertSame(4, $quadRooms[0]->pilgrims_count);
        $this->assertSame(3, $quadRooms[1]->pilgrims_count);

        $tripleRoom = Room::query()->where('departure_id', $departure->id)->where('room_type', 'triple')->withCount('pilgrims')->firstOrFail();
        $this->assertSame(3, $tripleRoom->pilgrims_count);
        $this->assertSame('T-01', $tripleRoom->room_number);

        $this->assertSame(0, Pilgrim::query()->where('departure_id', $departure->id)->whereNull('room_id')->count());
    }

    public function test_cannot_assign_pilgrim_to_full_room(): void
    {
        $admin = $this->admin();
        $departure = Departure::query()->create([
            'program_name' => 'Full Room Test',
            'program_kind' => 'umroh',
        ]);

        $room = Room::query()->create([
            'departure_id' => $departure->id,
            'room_type' => 'double',
            'room_number' => 'D-01',
            'capacity' => 2,
        ]);

        foreach (['Imam', 'Bimo'] as $name) {
            Pilgrim::query()->create([
                'departure_id' => $departure->id,
                'full_name' => $name,
                'room_type' => 'double',
                'room_id' => $room->id,
            ]);
        }

        $extra = Pilgrim::query()->create([
            'departure_id' => $departure->id,
            'full_name' => 'Extra Person',
            'room_type' => 'double',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.operations.grouping.assign', $departure), [
                'pilgrim_id' => $extra->id,
                'room_id' => $room->id,
            ])
            ->assertSessionHas('err');

        $this->assertNull($extra->fresh()->room_id);
    }

    public function test_pilgrim_transaction_updates_payment_summary(): void
    {
        $admin = $this->admin();
        $departure = Departure::query()->create([
            'program_name' => 'Haji Pembukuan',
            'program_kind' => 'haji',
        ]);

        $pilgrim = Pilgrim::query()->create([
            'departure_id' => $departure->id,
            'full_name' => 'Jamaah Haji',
            'room_type' => 'quad',
            'package_price' => 50000000,
            'haji_registration_id' => 'HJI-001',
            'haji_portion_number' => 'P-7788',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.operations.pilgrims.transactions.store', $pilgrim), [
                'type' => 'dp',
                'amount' => 10000000,
                'paid_at' => '2026-01-10',
                'notes' => 'DP awal',
            ])
            ->assertRedirect();

        $pilgrim->refresh();
        $this->assertSame(10000000, (int) $pilgrim->paid_amount);
        $this->assertSame('2026-01-10', $pilgrim->dp_date?->format('Y-m-d'));

        $this->actingAs($admin)
            ->post(route('admin.operations.pilgrims.transactions.store', $pilgrim), [
                'type' => 'pelunasan',
                'amount' => 40000000,
                'paid_at' => '2026-03-01',
            ])
            ->assertRedirect();

        $pilgrim->refresh();
        $this->assertSame(50000000, (int) $pilgrim->paid_amount);
        $this->assertSame('2026-03-01', $pilgrim->settlement_date?->format('Y-m-d'));
        $this->assertSame(0, $pilgrim->remainingBalance());
    }

    public function test_pilgrims_index_shows_payment_status(): void
    {
        $this->seed(\Database\Seeders\OperationsSeeder::class);

        $this->actingAs($this->admin())
            ->get(route('admin.operations.pilgrims.index'))
            ->assertOk()
            ->assertSee('Bayar')
            ->assertSee('100%')
            ->assertSee('0%');
    }

    public function test_haji_pilgrim_can_record_porsi_transaction(): void
    {
        $admin = $this->admin();
        $departure = Departure::query()->create([
            'program_name' => 'Haji Porsi Test',
            'program_kind' => 'haji',
        ]);

        $pilgrim = Pilgrim::query()->create([
            'departure_id' => $departure->id,
            'full_name' => 'Jamaah Porsi',
            'room_type' => 'double',
            'package_price' => 100000000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.operations.pilgrims.transactions.store', $pilgrim), [
                'type' => 'porsi',
                'amount' => 15000000,
                'paid_at' => '2026-02-01',
                'notes' => 'Bayar porsi',
            ])
            ->assertRedirect();

        $pilgrim->refresh();
        $this->assertSame(15000000, (int) $pilgrim->paid_amount);

        $this->actingAs($admin)
            ->get(route('admin.operations.pilgrims.show', $pilgrim))
            ->assertOk()
            ->assertSee('Porsi');
    }

    public function test_pilgrim_can_record_other_transaction(): void
    {
        $admin = $this->admin();
        $departure = Departure::query()->create([
            'program_name' => 'Umroh Lain-lain Test',
            'program_kind' => 'umroh',
        ]);

        $pilgrim = Pilgrim::query()->create([
            'departure_id' => $departure->id,
            'full_name' => 'Jamaah Vaksin',
            'room_type' => 'quad',
            'package_price' => 30000000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.operations.pilgrims.transactions.store', $pilgrim), [
                'type' => 'lain-lain',
                'amount' => 750000,
                'paid_at' => '2026-02-01',
                'notes' => 'Vaksin meningitis',
            ])
            ->assertRedirect();

        $pilgrim->refresh();
        $this->assertSame(750000, (int) $pilgrim->paid_amount);
        $this->assertDatabaseHas('pilgrim_transactions', [
            'pilgrim_id' => $pilgrim->id,
            'type' => 'lain-lain',
            'notes' => 'Vaksin meningitis',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.operations.pilgrims.show', $pilgrim))
            ->assertOk()
            ->assertSee('Lain-lain')
            ->assertSee('Vaksin meningitis');
    }

    public function test_transaction_can_upload_proof_and_auto_invoice(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $departure = Departure::query()->create([
            'program_name' => 'Invoice Test',
            'program_kind' => 'umroh',
        ]);

        $pilgrim = Pilgrim::query()->create([
            'departure_id' => $departure->id,
            'full_name' => 'Jamaah Invoice',
            'room_type' => 'quad',
            'package_price' => 30000000,
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->image('bukti-transfer.jpg');

        $this->actingAs($admin)
            ->post(route('admin.operations.pilgrims.transactions.store', $pilgrim), [
                'type' => 'dp',
                'amount' => 10000000,
                'paid_at' => '2026-02-01',
                'notes' => 'Transfer BCA',
                'proof' => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('ok');

        $transaction = $pilgrim->transactions()->firstOrFail();
        $this->assertTrue($transaction->hasProof());
        $this->assertTrue($transaction->hasInvoice());
        $this->assertStringStartsWith('INV-', $transaction->invoice_number);
        Storage::disk('public')->assertExists('payment-proofs/'.basename($transaction->proof_path));

        $this->actingAs($admin)
            ->get(route('admin.operations.pilgrims.transactions.invoice.show', [$pilgrim, $transaction]))
            ->assertOk()
            ->assertSee($transaction->invoice_number)
            ->assertSee('Jamaah Invoice')
            ->assertSee('Transfer BCA');

        $this->actingAs($admin)
            ->get(route('admin.operations.pilgrims.show', $pilgrim))
            ->assertOk()
            ->assertSee($transaction->invoice_number)
            ->assertSee('Print')
            ->assertSee('Lihat');
    }

    public function test_operations_seeder_creates_sample_data(): void
    {
        $this->seed(\Database\Seeders\OperationsSeeder::class);

        $this->assertDatabaseHas('departures', ['program_name' => 'Umroh Reguler 12 Hari — April 2026']);
        $this->assertDatabaseHas('rooms', ['room_number' => 'Q-01']);
        $this->assertDatabaseHas('pilgrims', ['full_name' => 'Abdul Hadi']);
        $this->assertDatabaseHas('pilgrims', ['full_name' => 'Haji Abdullah Rahman', 'haji_portion_number' => 'P-778812']);
    }

    public function test_recap_page_shows_departure_summary(): void
    {
        $admin = $this->admin();
        $departure = Departure::query()->create([
            'program_name' => 'Rekap Test',
            'program_kind' => 'umroh',
        ]);

        $room = Room::query()->create([
            'departure_id' => $departure->id,
            'room_type' => 'quad',
            'room_number' => 'Q-01',
            'capacity' => 4,
        ]);

        Pilgrim::query()->create([
            'departure_id' => $departure->id,
            'full_name' => 'Abdul Hadi',
            'room_type' => 'quad',
            'room_id' => $room->id,
            'package_price' => 25000000,
            'paid_amount' => 25000000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.operations.recap.show', $departure))
            ->assertOk()
            ->assertSee('Rekap Keberangkatan')
            ->assertSee('Q-01')
            ->assertSee('Abdul Hadi')
            ->assertSee('100%');
    }

    public function test_overpayment_is_allowed_and_reported(): void
    {
        $admin = $this->admin();
        $departure = Departure::query()->create([
            'program_name' => 'Overpay Test',
            'program_kind' => 'umroh',
        ]);

        $pilgrim = Pilgrim::query()->create([
            'departure_id' => $departure->id,
            'full_name' => 'Overpay Jamaah',
            'room_type' => 'quad',
            'package_price' => 30000000,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.operations.pilgrims.transactions.store', $pilgrim), [
                'type' => 'dp',
                'amount' => 35000000,
                'paid_at' => '2026-01-10',
            ])
            ->assertRedirect()
            ->assertSessionHas('ok');

        $pilgrim->refresh();
        $this->assertTrue($pilgrim->hasOverpayment());
        $this->assertSame(5000000, $pilgrim->overpaymentAmount());
        $this->assertSame('Lebih bayar', $pilgrim->paymentStatusLabel());
        $this->assertSame('Rp 5.000.000', $pilgrim->paymentStatusHint());

        $this->actingAs($admin)
            ->get(route('admin.operations.dashboard'))
            ->assertOk()
            ->assertSee('Jamaah lebih bayar')
            ->assertSee('Overpay Jamaah');
    }
}
