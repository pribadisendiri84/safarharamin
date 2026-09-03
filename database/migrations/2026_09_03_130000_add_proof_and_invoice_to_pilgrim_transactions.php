<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pilgrim_transactions', function (Blueprint $table) {
            $table->string('proof_path')->nullable()->after('notes');
            $table->string('invoice_number', 32)->nullable()->unique()->after('proof_path');
            $table->timestamp('invoice_created_at')->nullable()->after('invoice_number');
        });
    }

    public function down(): void
    {
        Schema::table('pilgrim_transactions', function (Blueprint $table) {
            $table->dropColumn(['proof_path', 'invoice_number', 'invoice_created_at']);
        });
    }
};
