<?php

namespace Database\Seeders;

use App\Models\Departure;
use App\Models\Package;
use App\Models\Pilgrim;
use App\Models\PilgrimTransaction;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Seeder;

class OperationsSeeder extends Seeder
{
    public function run(): void
    {
        if (Departure::query()->exists()) {
            return;
        }

        $adminId = User::query()->value('id');

        $umrohPackage = Package::query()->where('title', 'Umroh Reguler 12 Hari Jakarta')->first();
        $hajiPackage = Package::query()->where('title', 'Haji Plus 27 Hari')->first();

        $umroh = Departure::query()->create([
            'package_id' => $umrohPackage?->id,
            'program_name' => 'Umroh Reguler 12 Hari — April 2026',
            'program_kind' => 'umroh',
            'departure_date' => '2026-11-03',
            'airline' => $umrohPackage?->airline ?? 'Garuda Indonesia',
            'flight_number' => 'GA-980',
            'hotel_makkah' => $umrohPackage?->hotel_makkah ?? 'Azka Al Huda',
            'hotel_madinah' => $umrohPackage?->hotel_madinah ?? 'Al Haram',
            'notes' => 'Contoh data operasional — grouping room sudah terisi sebagian.',
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);

        $haji = Departure::query()->create([
            'package_id' => $hajiPackage?->id,
            'program_name' => 'Haji Plus 27 Hari — Musim 1447H',
            'program_kind' => 'haji',
            'departure_date' => '2027-05-20',
            'airline' => $hajiPackage?->airline ?? 'Garuda Indonesia',
            'flight_number' => 'GA-7720',
            'hotel_makkah' => $hajiPackage?->hotel_makkah ?? 'Fairmont Makkah',
            'hotel_madinah' => $hajiPackage?->hotel_madinah ?? 'Shaza Al Madina',
            'notes' => 'Contoh pembukuan DP/pelunasan dan data porsi haji.',
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);

        $this->seedUmrohGrouping($umroh, $adminId);
        $this->seedHajiSample($haji, $adminId);
    }

    private function seedUmrohGrouping(Departure $departure, ?int $adminId): void
    {
        $q01 = Room::query()->create([
            'departure_id' => $departure->id,
            'room_type' => 'quad',
            'room_number' => 'Q-01',
            'capacity' => 4,
        ]);

        foreach (['Abdul Hadi', 'Nurhayati', 'Ferio', 'Salsabila'] as $name) {
            Pilgrim::query()->create([
                'departure_id' => $departure->id,
                'room_id' => $q01->id,
                'full_name' => $name,
                'phone' => '0812'.random_int(10000000, 99999999),
                'gender' => in_array($name, ['Nurhayati', 'Salsabila'], true) ? 'female' : 'male',
                'room_type' => 'quad',
                'package_price' => 34500000,
                'paid_amount' => 10000000,
                'dp_date' => '2026-01-15',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);
        }

        $t01 = Room::query()->create([
            'departure_id' => $departure->id,
            'room_type' => 'triple',
            'room_number' => 'T-01',
            'capacity' => 3,
        ]);

        foreach (['Tita', 'Syahrial', 'Sarah'] as $name) {
            Pilgrim::query()->create([
                'departure_id' => $departure->id,
                'room_id' => $t01->id,
                'full_name' => $name,
                'phone' => '0813'.random_int(10000000, 99999999),
                'gender' => in_array($name, ['Tita', 'Sarah'], true) ? 'female' : 'male',
                'room_type' => 'triple',
                'package_price' => 36500000,
                'paid_amount' => 15000000,
                'dp_date' => '2026-02-01',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);
        }

        $d01 = Room::query()->create([
            'departure_id' => $departure->id,
            'room_type' => 'double',
            'room_number' => 'D-01',
            'capacity' => 2,
        ]);

        foreach (['Imam', 'Bimo'] as $name) {
            Pilgrim::query()->create([
                'departure_id' => $departure->id,
                'room_id' => $d01->id,
                'full_name' => $name,
                'phone' => '0817'.random_int(10000000, 99999999),
                'gender' => 'male',
                'room_type' => 'double',
                'package_price' => 38500000,
                'paid_amount' => 38500000,
                'dp_date' => '2026-01-20',
                'settlement_date' => '2026-03-10',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);
        }

        // Room belum penuh — untuk demo Auto Group / manual assign
        $q02 = Room::query()->create([
            'departure_id' => $departure->id,
            'room_type' => 'quad',
            'room_number' => 'Q-02',
            'capacity' => 4,
        ]);

        Pilgrim::query()->create([
            'departure_id' => $departure->id,
            'room_id' => $q02->id,
            'full_name' => 'Rina Wulandari',
            'phone' => '081234567801',
            'gender' => 'female',
            'room_type' => 'quad',
            'package_price' => 34500000,
            'paid_amount' => 5000000,
            'dp_date' => '2026-02-20',
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);

        // Jamaah belum group
        $ungrouped = [
            ['Ahmad Fauzi', 'quad', 'male', 34500000],
            ['Dewi Lestari', 'quad', 'female', 34500000],
            ['Yusuf Pratama', 'triple', 'male', 36500000],
            ['Maya Sari', 'triple', 'female', 36500000],
            ['Hendra Gunawan', 'double', 'male', 38500000],
        ];

        foreach ($ungrouped as [$name, $roomType, $gender, $price]) {
            Pilgrim::query()->create([
                'departure_id' => $departure->id,
                'room_id' => null,
                'full_name' => $name,
                'phone' => '0812'.random_int(10000000, 99999999),
                'gender' => $gender,
                'room_type' => $roomType,
                'package_price' => $price,
                'paid_amount' => 0,
                'notes' => 'Belum di-group — coba Auto Group atau assign manual.',
                'created_by' => $adminId,
                'updated_by' => $adminId,
            ]);
        }
    }

    private function seedHajiSample(Departure $departure, ?int $adminId): void
    {
        $room = Room::query()->create([
            'departure_id' => $departure->id,
            'room_type' => 'double',
            'room_number' => 'D-01',
            'capacity' => 2,
        ]);

        $paidPilgrim = Pilgrim::query()->create([
            'departure_id' => $departure->id,
            'room_id' => $room->id,
            'full_name' => 'Haji Abdullah Rahman',
            'phone' => '081298765432',
            'gender' => 'male',
            'room_type' => 'double',
            'haji_registration_id' => 'HJI-2026-00451',
            'haji_portion_number' => 'P-778812',
            'package_price' => 275000000,
            'paid_amount' => 0,
            'notes' => 'Sudah lunas — contoh riwayat transaksi di bawah.',
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);

        PilgrimTransaction::query()->create([
            'pilgrim_id' => $paidPilgrim->id,
            'type' => PilgrimTransaction::TYPE_DP,
            'amount' => 50000000,
            'paid_at' => '2025-11-10',
            'notes' => 'DP awal 50 juta',
            'created_by' => $adminId,
        ]);

        PilgrimTransaction::query()->create([
            'pilgrim_id' => $paidPilgrim->id,
            'type' => PilgrimTransaction::TYPE_DP,
            'amount' => 25000000,
            'paid_at' => '2026-01-05',
            'notes' => 'DP tahap 2',
            'created_by' => $adminId,
        ]);

        PilgrimTransaction::query()->create([
            'pilgrim_id' => $paidPilgrim->id,
            'type' => PilgrimTransaction::TYPE_SETTLEMENT,
            'amount' => 200000000,
            'paid_at' => '2026-03-15',
            'notes' => 'Pelunasan',
            'created_by' => $adminId,
        ]);

        $paidPilgrim->refreshPaymentSummary();

        Pilgrim::query()->create([
            'departure_id' => $departure->id,
            'room_id' => $room->id,
            'full_name' => 'Haji Siti Aminah',
            'phone' => '081376543210',
            'gender' => 'female',
            'room_type' => 'double',
            'haji_registration_id' => 'HJI-2026-00452',
            'haji_portion_number' => 'P-778813',
            'package_price' => 275000000,
            'paid_amount' => 75000000,
            'dp_date' => '2026-02-01',
            'notes' => 'Masih cicilan — sisa belum lunas.',
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);

        Pilgrim::query()->create([
            'departure_id' => $departure->id,
            'room_id' => null,
            'full_name' => 'Haji Muhammad Ridwan',
            'phone' => '081112223344',
            'gender' => 'male',
            'room_type' => 'quad',
            'haji_registration_id' => 'HJI-2026-00460',
            'haji_portion_number' => 'P-778820',
            'package_price' => 275000000,
            'paid_amount' => 30000000,
            'dp_date' => '2026-02-28',
            'notes' => 'Belum group — tipe quad, menunggu pasangan kamar.',
            'created_by' => $adminId,
            'updated_by' => $adminId,
        ]);
    }
}
