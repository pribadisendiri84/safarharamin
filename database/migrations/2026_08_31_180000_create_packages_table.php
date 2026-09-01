<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type')->default('umroh');
            $table->string('departure_city');
            $table->date('departure_date')->nullable();
            $table->unsignedSmallInteger('duration_days');
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('original_price')->nullable();
            $table->string('hotel_makkah')->nullable();
            $table->string('hotel_madinah')->nullable();
            $table->unsignedTinyInteger('hotel_stars')->default(4);
            $table->string('airline')->nullable();
            $table->string('room_type')->default('quad');
            $table->unsignedSmallInteger('seats_total')->default(40);
            $table->unsignedSmallInteger('seats_left')->default(40);
            $table->json('facilities')->nullable();
            $table->text('itinerary')->nullable();
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_hot')->default(false);
            $table->string('status')->default('published');
            $table->timestamps();

            $table->index(['status', 'type']);
            $table->index(['status', 'departure_city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
