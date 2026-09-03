<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gallery_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('home_sort')->nullable()->after('show_on_home');
        });

        $items = DB::table('gallery_items')
            ->where('show_on_home', true)
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->limit(8)
            ->pluck('id');

        foreach ($items as $index => $id) {
            DB::table('gallery_items')->where('id', $id)->update(['home_sort' => $index + 1]);
        }
    }

    public function down(): void
    {
        Schema::table('gallery_items', function (Blueprint $table) {
            $table->dropColumn('home_sort');
        });
    }
};
