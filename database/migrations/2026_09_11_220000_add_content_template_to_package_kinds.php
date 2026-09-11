<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_kinds', function (Blueprint $table) {
            $table->text('description')->nullable()->after('is_active');
            $table->string('hotel_makkah', 120)->nullable()->after('description');
            $table->boolean('hotel_makkah_setaraf')->default(false)->after('hotel_makkah');
            $table->string('hotel_madinah', 120)->nullable()->after('hotel_makkah_setaraf');
            $table->boolean('hotel_madinah_setaraf')->default(false)->after('hotel_madinah');
            $table->json('facilities')->nullable()->after('hotel_madinah_setaraf');
            $table->json('exclusions')->nullable()->after('facilities');
        });
    }

    public function down(): void
    {
        Schema::table('package_kinds', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'hotel_makkah',
                'hotel_makkah_setaraf',
                'hotel_madinah',
                'hotel_madinah_setaraf',
                'facilities',
                'exclusions',
            ]);
        });
    }
};
