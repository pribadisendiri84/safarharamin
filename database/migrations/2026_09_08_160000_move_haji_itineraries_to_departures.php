<?php

use App\Models\Departure;
use App\Support\HajiPlusPage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $table->string('hijri_label', 80)->nullable()->after('departure_date');
            $table->string('itinerary_pdf_path', 500)->nullable()->after('hijri_label');
        });

        if (! Schema::hasTable('haji_page_itineraries')) {
            return;
        }

        $defaults = HajiPlusPage::departureDefaults();

        foreach (DB::table('haji_page_itineraries')->orderBy('departure_date')->get() as $row) {
            $departure = Departure::query()
                ->where('program_kind', 'haji')
                ->whereDate('departure_date', $row->departure_date)
                ->first();

            if ($departure) {
                $departure->update([
                    'hijri_label' => $row->hijri_label,
                    'itinerary_pdf_path' => $row->file_path,
                ]);

                continue;
            }

            Departure::query()->create([
                'source' => Departure::SOURCE_HAJI_PAGE,
                'program_kind' => 'haji',
                'program_name' => $defaults['program_name'],
                'departure_date' => $row->departure_date,
                'hijri_label' => $row->hijri_label,
                'itinerary_pdf_path' => $row->file_path,
                'airline' => $defaults['airline'],
                'hotel_madinah' => $defaults['hotel_madinah'],
                'hotel_makkah' => $defaults['hotel_makkah'],
                'program_snapshot' => HajiPlusPage::operationalSnapshot(),
            ]);
        }

        Schema::dropIfExists('haji_page_itineraries');
    }

    public function down(): void
    {
        Schema::table('departures', function (Blueprint $table) {
            $table->dropColumn(['hijri_label', 'itinerary_pdf_path']);
        });
    }
};
