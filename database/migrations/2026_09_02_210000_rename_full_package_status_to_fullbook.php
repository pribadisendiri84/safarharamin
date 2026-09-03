<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('packages')->where('status', 'full')->update(['status' => 'fullbook']);
    }

    public function down(): void
    {
        DB::table('packages')->where('status', 'fullbook')->update(['status' => 'full']);
    }
};
