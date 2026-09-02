<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedBigInteger('price_quad')->nullable()->after('price');
            $table->unsignedBigInteger('price_triple')->nullable()->after('price_quad');
            $table->unsignedBigInteger('price_double')->nullable()->after('price_triple');
            $table->json('exclusions')->nullable()->after('facilities');
            $table->string('price_note', 180)->nullable()->after('original_price');
        });

        DB::table('packages')->whereNull('price_quad')->update([
            'price_quad' => DB::raw('price'),
        ]);
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['price_quad', 'price_triple', 'price_double', 'exclusions', 'price_note']);
        });
    }
};
