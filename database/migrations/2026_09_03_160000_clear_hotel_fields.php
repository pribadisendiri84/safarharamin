<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('packages')->update([
            'hotel_makkah' => null,
            'hotel_madinah' => null,
            'hotel_transit' => null,
            'hotel_maktab' => null,
        ]);

        DB::table('departures')->update([
            'hotel_makkah' => null,
            'hotel_madinah' => null,
            'hotel_transit' => null,
            'hotel_maktab' => null,
        ]);
    }

    public function down(): void
    {
        // Data hotel diisi manual — tidak dikembalikan ke nilai demo.
    }
};
