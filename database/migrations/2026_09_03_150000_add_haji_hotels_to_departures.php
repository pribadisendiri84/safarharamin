<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $table->string('hotel_transit')->nullable()->after('hotel_madinah');
            $table->string('hotel_maktab')->nullable()->after('hotel_transit');
        });
    }

    public function down(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $table->dropColumn(['hotel_transit', 'hotel_maktab']);
        });
    }
};
