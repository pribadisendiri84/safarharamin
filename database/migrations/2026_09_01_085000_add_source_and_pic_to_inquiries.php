<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('source', 20)->default('website')->after('kind');
            $table->foreignId('pic_id')->nullable()->after('package_id')->constrained('users')->nullOnDelete();
            $table->index(['source', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropForeign(['pic_id']);
            $table->dropIndex(['source', 'status']);
            $table->dropColumn(['source', 'pic_id']);
        });
    }
};
