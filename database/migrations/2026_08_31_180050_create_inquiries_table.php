<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('kind')->default('daftar');
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->unsignedTinyInteger('pax')->nullable();
            $table->unsignedBigInteger('budget')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('baru');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
