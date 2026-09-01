<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->uuid('session_id');
            $table->string('path', 255)->nullable();
            $table->string('landing_path', 255)->nullable();
            $table->string('referrer_host', 120)->nullable();
            $table->string('utm_source', 80)->nullable();
            $table->string('utm_medium', 80)->nullable();
            $table->string('utm_campaign', 120)->nullable();
            $table->string('wa_placement', 20)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type', 'created_at']);
            $table->index(['session_id', 'created_at']);
            $table->index(['path', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_events');
    }
};
