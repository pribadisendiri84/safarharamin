<?php

use Database\Seeders\HotelSeeder;
use Database\Seeders\PackageKindSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_kinds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'sort_order']);
        });

        (new PackageKindSeeder)->run();

        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('package_kind_id')->nullable()->after('type')->constrained('package_kinds')->nullOnDelete();
            $table->boolean('hotel_makkah_setaraf')->default(false)->after('hotel_makkah');
            $table->boolean('hotel_madinah_setaraf')->default(false)->after('hotel_madinah');
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->unsignedTinyInteger('stars')->default(4)->after('location');
        });

        (new HotelSeeder)->run();

        DB::table('packages')->where('type', 'umroh_ramadhan')->update(['type' => 'umroh']);
        DB::table('packages')->where('type', 'haji_furoda')->update(['type' => 'haji_plus']);

        $defaultKindId = DB::table('package_kinds')->where('slug', 'arafah')->value('id');
        if ($defaultKindId) {
            DB::table('packages')->whereNull('package_kind_id')->update(['package_kind_id' => $defaultKindId]);
        }
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('package_kind_id');
            $table->dropColumn(['hotel_makkah_setaraf', 'hotel_madinah_setaraf']);
        });
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn('stars');
        });
        Schema::dropIfExists('package_kinds');
    }
};
