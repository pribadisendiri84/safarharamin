<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $table->boolean('show_on_haji_page')->default(false)->after('itinerary_pdf_path');
        });

        DB::table('departures')
            ->whereNotNull('itinerary_pdf_path')
            ->where('program_kind', 'haji')
            ->where('source', 'haji_page')
            ->update(['show_on_haji_page' => true]);
    }

    public function down(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $table->dropColumn('show_on_haji_page');
        });
    }
};
