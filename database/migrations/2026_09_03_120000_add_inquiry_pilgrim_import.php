<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->timestamp('pilgrims_imported_at')->nullable()->after('closed_at');
        });

        Schema::table('pilgrims', function (Blueprint $table) {
            $table->foreignId('inquiry_id')->nullable()->after('departure_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pilgrims', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inquiry_id');
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn('pilgrims_imported_at');
        });
    }
};
