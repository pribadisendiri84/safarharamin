<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pilgrim_transactions', function (Blueprint $table) {
            $table->foreignId('refunded_transaction_id')
                ->nullable()
                ->after('pilgrim_id')
                ->unique()
                ->constrained('pilgrim_transactions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pilgrim_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('refunded_transaction_id');
        });
    }
};
