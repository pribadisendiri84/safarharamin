<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('packages')->where('status', 'full')->update(['status' => 'fullbooked']);
    }

    public function down(): void
    {
        DB::table('packages')->where('status', 'fullbooked')->update(['status' => 'full']);
    }
};
