<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airline;
use App\Models\HajiPageItinerary;
use App\Models\Hotel;
use App\Services\HajiPageItineraryStore;
use App\Services\PackageImageStore;
use App\Support\HajiPlusPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class HajiPlusPageController extends Controller
{
    public function edit()
    {
        return view('admin.haji-plus.edit', [
            'page' => HajiPlusPage::content(),
            'itineraries' => HajiPlusPage::itineraries(),
            'airlines' => Airline::query()->orderBy('sort_order')->orderBy('name')->get(['name', 'logo']),
            'hotelLocations' => [
                Hotel::LOCATION_MADINAH => Hotel::LOCATIONS[Hotel::LOCATION_MADINAH],
                Hotel::LOCATION_MAKKAH => Hotel::LOCATIONS[Hotel::LOCATION_MAKKAH],
            ],
            'hotelOptionsByLocation' => [
                Hotel::LOCATION_MADINAH => Hotel::options(Hotel::LOCATION_MADINAH),
                Hotel::LOCATION_MAKKAH => Hotel::options(Hotel::LOCATION_MAKKAH),
            ],
        ]);
    }

    public function update(Request $request, PackageImageStore $images, HajiPageItineraryStore $itineraryStore)
    {
        $data = $request->validate([
            'hero.badge' => ['required', 'string', 'max:40'],
            'hero.season' => ['required', 'string', 'max:40'],
            'hero.title' => ['required', 'string', 'max:120'],
            'hero.subtitle' => ['required', 'string', 'max:400'],
            'hero.starting_price' => ['nullable', 'string', 'max:40'],
            'hero.show_quota' => ['nullable', 'boolean'],
            'hero.image_file' => ['nullable', 'image', 'max:4096'],
            'rooms' => ['required', 'array', 'size:4'],
            'rooms.*.label' => ['required', 'string', 'max:40'],
            'rooms.*.occupancy' => ['required', 'string', 'max:40'],
            'rooms.*.price_label' => ['required', 'string', 'max:40'],
            'rooms.*.price_note' => ['required', 'string', 'max:20'],
            'rooms.*.image_file' => ['nullable', 'image', 'max:4096'],
            'rooms.*.is_featured' => ['nullable', 'boolean'],
            'benefits' => ['required', 'array', 'size:5'],
            'benefits.*.title' => ['required', 'string', 'max:60'],
            'benefits.*.description' => ['required', 'string', 'max:160'],
            'benefits.*.icon' => ['required', 'string', 'max:40'],
            'partner_airlines' => ['nullable', 'array', 'max:8'],
            'partner_airlines.*' => ['required', 'string', 'max:80'],
            'hotels' => ['required', 'array', 'min:1', 'max:6'],
            'hotels.*.master_name' => ['required', 'string', 'max:80'],
            'hotels.*.master_location' => ['required', Rule::in([
                Hotel::LOCATION_MADINAH,
                Hotel::LOCATION_MAKKAH,
            ])],
            'hotels.*.distance' => ['required', 'string', 'max:80'],
            'hotels.*.features_text' => ['required', 'string', 'max:400'],
            'hotels.*.badge' => ['required', 'string', 'max:40'],
            'airline.description' => ['required', 'string', 'max:400'],
            'airline.points_text' => ['required', 'string', 'max:400'],
            'flow' => ['required', 'array', 'size:4'],
            'flow.*.title' => ['required', 'string', 'max:60'],
            'flow.*.description' => ['required', 'string', 'max:160'],
            'flow.*.icon' => ['required', 'string', 'max:40'],
            'cta.title' => ['required', 'string', 'max:120'],
            'cta.description' => ['required', 'string', 'max:300'],
            'cta.note' => ['required', 'string', 'max:80'],
            'itinerary_departure_dates' => ['nullable', 'array'],
            'itinerary_departure_dates.*' => ['nullable', 'date'],
            'itinerary_hijri_labels' => ['nullable', 'array'],
            'itinerary_hijri_labels.*' => ['nullable', 'string', 'max:80'],
            'itinerary_pdfs' => ['nullable', 'array'],
            'itinerary_pdfs.*' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'delete_itineraries' => ['nullable', 'array'],
            'delete_itineraries.*' => ['integer', 'exists:haji_page_itineraries,id'],
        ]);

        $current = HajiPlusPage::content();

        $hero = $data['hero'];
        $hero['show_quota'] = $request->boolean('hero.show_quota') ? '1' : '0';
        $hero['starting_price'] = trim((string) ($hero['starting_price'] ?? ''));
        $hero['image'] = $this->storeImage($request, $images, 'hero.image_file', 'haji-hero', $current['hero']['image'] ?? '');
        unset($hero['image_file']);

        $rooms = [];
        foreach ($data['rooms'] as $index => $room) {
            $rooms[] = [
                'key' => $current['rooms'][$index]['key'] ?? 'room-'.$index,
                'label' => trim($room['label']),
                'occupancy' => trim($room['occupancy']),
                'price_label' => trim($room['price_label']),
                'price_note' => trim($room['price_note']),
                'is_featured' => $request->boolean('rooms.'.$index.'.is_featured') ? '1' : '0',
                'image' => $this->storeImage($request, $images, 'rooms.'.$index.'.image_file', 'haji-room-'.$index, $current['rooms'][$index]['image'] ?? ''),
            ];
        }

        $hotels = [];
        foreach ($data['hotels'] as $index => $hotel) {
            $masterName = trim((string) $hotel['master_name']);
            $masterLocation = trim((string) $hotel['master_location']);

            $hotels[] = [
                'master_name' => $masterName,
                'master_location' => $masterLocation,
                'distance' => trim($hotel['distance']),
                'features_text' => trim($hotel['features_text']),
                'badge' => trim($hotel['badge']),
            ];
        }

        $partnerAirlines = array_values(array_unique(array_map(
            fn (string $name) => trim($name),
            $data['partner_airlines'] ?? [],
        )));

        $airline = [
            'description' => trim($data['airline']['description']),
            'points_text' => trim($data['airline']['points_text']),
            'image' => $current['airline']['image'] ?? HajiPlusPage::defaults()['airline']['image'],
        ];

        HajiPlusPage::save([
            'hero' => $hero,
            'rooms' => $rooms,
            'benefits' => $data['benefits'],
            'partner_airlines' => $partnerAirlines,
            'hotels' => $hotels,
            'airline' => $airline,
            'flow' => $data['flow'],
            'cta' => $data['cta'],
        ]);

        $this->syncItineraries($request, $itineraryStore);

        return redirect()->route('admin.haji-plus.edit')->with('ok', 'Halaman Haji Plus tersimpan.');
    }

    private function syncItineraries(Request $request, HajiPageItineraryStore $store): void
    {
        $deleteIds = array_map('intval', $request->input('delete_itineraries', []));
        if ($deleteIds !== []) {
            HajiPageItinerary::query()
                ->whereIn('id', $deleteIds)
                ->get()
                ->each(function (HajiPageItinerary $item) use ($store) {
                    $store->delete($item->file_path);
                    $item->delete();
                });
        }

        foreach ($request->input('itinerary_departure_dates', []) as $index => $dateRaw) {
            $date = trim((string) $dateRaw);
            $hijriLabels = $request->input('itinerary_hijri_labels', []);
            $hijriLabel = trim((string) (is_array($hijriLabels) ? ($hijriLabels[$index] ?? '') : ''));
            $files = $request->file('itinerary_pdfs', []);
            $file = is_array($files) ? ($files[$index] ?? null) : null;

            if ($date === '' && $hijriLabel === '' && ! $file) {
                continue;
            }

            if ($date === '') {
                throw ValidationException::withMessages([
                    "itinerary_departure_dates.{$index}" => 'Isi tanggal keberangkatan untuk itinerary.',
                ]);
            }

            if ($hijriLabel === '') {
                throw ValidationException::withMessages([
                    "itinerary_hijri_labels.{$index}" => 'Isi tanggal Hijriah (mis. 1448 H).',
                ]);
            }

            if (! $file || ! $file->isValid()) {
                throw ValidationException::withMessages([
                    "itinerary_pdfs.{$index}" => 'Unggah file PDF itinerary.',
                ]);
            }

            HajiPageItinerary::query()->create([
                'kind' => 'official',
                'departure_date' => $date,
                'hijri_label' => $hijriLabel,
                'file_path' => $store->store($file, 'haji-itinerary '.$date),
            ]);
        }

        HajiPageItinerary::syncSortOrder();
    }

    private function storeImage(
        Request $request,
        PackageImageStore $images,
        string $input,
        string $title,
        string $previous,
    ): string {
        if (! $request->hasFile($input)) {
            return $previous;
        }

        $path = $images->store($request->file($input), $title, 'haji-plus');
        $this->deleteStoredImage($previous);

        return $path;
    }

    private function deleteStoredImage(string $path): void
    {
        if ($path === '' || ! str_starts_with($path, '/storage/')) {
            return;
        }

        Storage::disk('public')->delete(ltrim(substr($path, strlen('/storage/')), '/'));
    }
}
