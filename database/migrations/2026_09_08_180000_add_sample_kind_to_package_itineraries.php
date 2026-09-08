<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_itineraries', function (Blueprint $table) {
            $table->string('kind', 20)->default('official')->after('package_id');
            $table->string('label', 120)->nullable()->after('kind');
            $table->date('departure_date')->nullable()->change();
        });

        DB::table('package_itineraries')->update(['kind' => 'official']);
    }

    public function down(): void
    {
        Schema::table('package_itineraries', function (Blueprint $table) {
            $table->dropColumn(['kind', 'label']);
            $table->date('departure_date')->nullable(false)->change();
        });
    }
};
