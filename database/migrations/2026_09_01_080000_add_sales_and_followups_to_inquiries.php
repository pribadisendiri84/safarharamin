<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->unsignedTinyInteger('sold_pax')->nullable()->after('status');
            $table->unsignedBigInteger('sold_amount')->nullable()->after('sold_pax');
            $table->timestamp('closed_at')->nullable()->after('sold_amount');
            $table->boolean('seats_applied')->default(false)->after('closed_at');
        });

        Schema::create('inquiry_follow_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_follow_ups');

        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['sold_pax', 'sold_amount', 'closed_at', 'seats_applied']);
        });
    }
};
