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
            $table->string('card_badge_preset', 40)->nullable()->after('is_hot');
            $table->string('card_badge_icon', 20)->nullable()->after('card_badge_preset');
            $table->string('card_badge_text', 40)->nullable()->after('card_badge_icon');
            $table->string('card_badge_position', 20)->nullable()->after('card_badge_text');
        });

        DB::table('packages')->where('is_hot', true)->update([
            'card_badge_preset' => 'kuota_terbatas',
            'card_badge_icon' => 'hourglass',
            'card_badge_text' => 'Kuota Terbatas',
            'card_badge_position' => 'top_left',
        ]);
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'card_badge_preset',
                'card_badge_icon',
                'card_badge_text',
                'card_badge_position',
            ]);
        });
    }
};
