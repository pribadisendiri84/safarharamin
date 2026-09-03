<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->unsignedTinyInteger('home_sort')->nullable()->after('is_featured');
        });

        $ids = DB::table('packages')
            ->where('is_featured', true)
            ->whereNull('deleted_at')
            ->orderByDesc('is_hot')
            ->orderBy('price')
            ->limit(8)
            ->pluck('id');

        foreach ($ids as $index => $id) {
            DB::table('packages')->where('id', $id)->update(['home_sort' => $index + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('home_sort');
        });
    }
};
