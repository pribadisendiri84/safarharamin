<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('haji_page_itineraries', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->string('label', 120)->nullable();
            $table->date('departure_date')->nullable();
            $table->string('file_path', 500);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['kind', 'departure_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('haji_page_itineraries');
    }
};
