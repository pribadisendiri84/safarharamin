<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('program_kind', 20)->nullable()->after('package_id');
        });

        Schema::table('departures', function (Blueprint $table) {
            $table->string('source', 20)->default('manual')->after('package_id');
            $table->json('program_snapshot')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn('program_kind');
        });

        Schema::table('departures', function (Blueprint $table) {
            $table->dropColumn(['source', 'program_snapshot']);
        });
    }
};
