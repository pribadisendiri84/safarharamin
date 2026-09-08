<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('haji_page_itineraries', function (Blueprint $table) {
            $table->string('hijri_label', 80)->nullable()->after('departure_date');
        });

        DB::table('haji_page_itineraries')->where('kind', 'sample')->delete();
    }

    public function down(): void
    {
        Schema::table('haji_page_itineraries', function (Blueprint $table) {
            $table->dropColumn('hijri_label');
        });
    }
};
