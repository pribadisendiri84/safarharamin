<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->date('departure_date_end')->nullable()->after('departure_date');
            $table->string('departure_date_display', 10)->default('single')->after('departure_date_end');
            $table->boolean('show_seats')->default(true)->after('seats_left');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['departure_date_end', 'departure_date_display', 'show_seats']);
        });
    }
};
