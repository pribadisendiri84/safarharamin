<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('program_name');
            $table->string('program_kind', 16)->default('umroh');
            $table->date('departure_date')->nullable();
            $table->string('airline')->nullable();
            $table->string('flight_number')->nullable();
            $table->string('hotel_makkah')->nullable();
            $table->string('hotel_madinah')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departure_id')->constrained()->cascadeOnDelete();
            $table->string('room_type', 16);
            $table->string('room_number', 16);
            $table->unsignedTinyInteger('capacity');
            $table->timestamps();

            $table->unique(['departure_id', 'room_number']);
        });

        Schema::create('pilgrims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('departure_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('phone', 32)->nullable();
            $table->string('gender', 16)->nullable();
            $table->string('room_type', 16);
            $table->string('haji_registration_id')->nullable();
            $table->string('haji_portion_number')->nullable();
            $table->unsignedBigInteger('package_price')->default(0);
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->date('dp_date')->nullable();
            $table->date('settlement_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pilgrim_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pilgrim_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24);
            $table->unsignedBigInteger('amount');
            $table->date('paid_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pilgrim_transactions');
        Schema::dropIfExists('pilgrims');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('departures');
    }
};
