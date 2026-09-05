<?php

use App\Models\Pic;
use Database\Seeders\PicSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone', 32)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique('name');
            $table->index(['is_active', 'sort_order']);
        });

        Schema::table('pilgrims', function (Blueprint $table) {
            $table->foreignId('pic_id')->nullable()->after('inquiry_id')->constrained('pics')->nullOnDelete();
        });

        (new PicSeeder)->run();
        $this->backfillPilgrimPics();
    }

    public function down(): void
    {
        Schema::table('pilgrims', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pic_id');
        });
        Schema::dropIfExists('pics');
    }

    private function backfillPilgrimPics(): void
    {
        if (! Schema::hasTable('inquiries') || ! Schema::hasColumn('inquiries', 'pic_id')) {
            return;
        }

        $rows = DB::table('pilgrims')
            ->join('inquiries', 'inquiries.id', '=', 'pilgrims.inquiry_id')
            ->join('users', 'users.id', '=', 'inquiries.pic_id')
            ->whereNull('pilgrims.pic_id')
            ->whereNotNull('users.name')
            ->select('pilgrims.id as pilgrim_id', 'users.name as pic_name')
            ->get();

        foreach ($rows as $row) {
            $pic = Pic::firstOrCreateFromName((string) $row->pic_name);
            if (! $pic) {
                continue;
            }

            DB::table('pilgrims')->where('id', $row->pilgrim_id)->update(['pic_id' => $pic->id]);
        }
    }
};
