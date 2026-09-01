<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'users',
        'packages',
        'gallery_items',
        'testimonials',
        'inquiries',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                $table->softDeletes();

                if ($tableName !== 'users') {
                    $table->index('deleted_at');
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropForeign(['updated_by']);
                $table->dropColumn(['created_by', 'updated_by', 'deleted_at']);
            });
        }
    }
};
