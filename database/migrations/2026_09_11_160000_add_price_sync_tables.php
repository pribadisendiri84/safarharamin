<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('source_key', 120)->nullable()->unique()->after('slug');
        });

        Schema::create('price_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('trigger')->default('manual');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('waiting_approval');
            $table->unsignedSmallInteger('total_found')->default(0);
            $table->unsignedSmallInteger('total_new')->default(0);
            $table->unsignedSmallInteger('total_changed')->default(0);
            $table->unsignedSmallInteger('total_unchanged')->default(0);
            $table->unsignedSmallInteger('total_removed')->default(0);
            $table->unsignedSmallInteger('total_applied')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('price_sync_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_sync_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_key', 120)->nullable();
            $table->string('external_id', 40)->nullable();
            $table->string('change_status');
            $table->json('existing_snapshot')->nullable();
            $table->json('incoming_snapshot')->nullable();
            $table->json('diff_fields')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['price_sync_run_id', 'change_status']);
            $table->index('source_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_sync_changes');
        Schema::dropIfExists('price_sync_runs');

        Schema::table('packages', function (Blueprint $table) {
            $table->dropUnique(['source_key']);
            $table->dropColumn('source_key');
        });
    }
};
