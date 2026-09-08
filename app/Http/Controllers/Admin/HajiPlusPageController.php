<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airline;
use App\Models\Departure;
use App\Models\Hotel;
use App\Services\HajiPageItineraryStore;
use App\Services\PackageImageStore;
use App\Support\HajiPlusPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class HajiPlusPageController extends Controller
{
    public function edit()
    {
        return view('admin.haji-plus.edit', [
            'page' => HajiPlusPage::content(),
            'defaultHeroImage' => HajiPlusPage::defaultHeroImage(),
            'hajiDepartures' => Departure::query()
                ->where('program_kind', 'haji')
                ->where('source', Departure::SOURCE_HAJI_PAGE)
                ->orderBy('departure_date')
                ->orderBy('id')
                ->get(),
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
            'hero.starting_price' => ['nullable', 'integer', 'min:0'],
            'hero.show_quota' => ['nullable', 'boolean'],
            'hero.image' => ['nullable', 'string', 'max:500'],
            'hero.image_file' => ['nullable', 'image', 'max:4096'],
            'delete_hero_images' => ['nullable', 'array'],
            'delete_hero_images.*' => ['string', 'max:500'],
            'rooms' => ['required', 'array', 'size:4'],
            'rooms.*.label' => ['required', 'string', 'max:40'],
            'rooms.*.occupancy' => ['required', 'string', 'max:40'],
            'rooms.*.price' => ['required', 'integer', 'min:1'],
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
            'airline.show_names' => ['nullable', 'boolean'],
            'flow' => ['required', 'array', 'size:4'],
            'flow.*.title' => ['required', 'string', 'max:60'],
            'flow.*.description' => ['required', 'string', 'max:160'],
            'flow.*.icon' => ['required', 'string', 'max:40'],
            'cta.title' => ['required', 'string', 'max:120'],
            'cta.description' => ['required', 'string', 'max:300'],
            'cta.note' => ['required', 'string', 'max:80'],
            'itinerary_visible_departures' => ['nullable', 'array'],
            'itinerary_visible_departures.*' => ['integer', 'exists:departures,id'],
            'delete_sample_itineraries' => ['nullable', 'array', 'max:2'],
            'delete_sample_itineraries.*' => ['string', 'max:500'],
            'sample_itinerary_existing' => ['nullable', 'array', 'max:2'],
            'sample_itinerary_existing.*.label' => ['nullable', 'string', 'max:120'],
            'sample_itinerary_existing.*.file_path' => ['nullable', 'string', 'max:500'],
            'sample_itinerary_labels' => ['nullable', 'array', 'max:2'],
            'sample_itinerary_labels.*' => ['nullable', 'string', 'max:120'],
            'sample_itinerary_pdfs' => ['nullable', 'array', 'max:2'],
            'sample_itinerary_pdfs.*' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'detail_program.badge' => ['required', 'string', 'max:40'],
            'detail_program.title' => ['required', 'string', 'max:120'],
            'detail_program.deposit_summary' => ['required', 'string', 'max:300'],
            'detail_program.deposit_idr_note' => ['required', 'string', 'max:80'],
            'detail_program.facilities_text' => ['required', 'string', 'max:2000'],
            'detail_program.documents_text' => ['required', 'string', 'max:2000'],
            'detail_program.requirements_text' => ['required', 'string', 'max:2000'],
            'detail_program.closing_line' => ['required', 'string', 'max:120'],
            'detail_program.faq' => ['required', 'array', 'size:5'],
            'detail_program.faq.*.question' => ['required', 'string', 'max:200'],
            'detail_program.faq.*.answer' => ['required', 'string', 'max:1000'],
        ]);

        $current = HajiPlusPage::content();

        $hero = $data['hero'];
        $hero['show_quota'] = $request->boolean('hero.show_quota') ? '1' : '0';
        $hero['starting_price'] = max(0, (int) ($hero['starting_price'] ?? 0));
        $hero = $this->syncHeroImages($request, $images, $current['hero'], $hero);
        unset($hero['image_file']);

        $rooms = [];
        foreach ($data['rooms'] as $index => $room) {
            $rooms[] = [
                'key' => $current['rooms'][$index]['key'] ?? 'room-'.$index,
                'label' => trim($room['label']),
                'occupancy' => trim($room['occupancy']),
                'price' => max(0, (int) ($room['price'] ?? 0)),
                'price_note' => trim($room['price_note']) ?: '/jamaah',
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
            'show_names' => $request->boolean('airline.show_names') ? '1' : '0',
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
            'detail_program' => HajiPlusPage::normalizeDetailProgram($data['detail_program']),
            'sample_itineraries' => $this->syncSampleItineraries($request, $itineraryStore, $current),
        ]);

        $this->syncItineraryVisibility($request);

        return redirect()->route('admin.haji-plus.edit')->with('ok', 'Halaman Haji Plus tersimpan.');
    }

    /**
     * @param  array<string, mixed>  $current
     * @return list<array{label: string, file_path: string}>
     */
    private function syncSampleItineraries(Request $request, HajiPageItineraryStore $store, array $current): array
    {
        $items = [];

        foreach ($request->input('sample_itinerary_existing', []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $path = trim((string) ($row['file_path'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));

            if ($path === '' || $label === '') {
                continue;
            }

            if (in_array($path, $request->input('delete_sample_itineraries', []), true)) {
                $store->delete($path);
                continue;
            }

            $items[] = [
                'label' => $label,
                'file_path' => $path,
            ];
        }

        $labels = $request->input('sample_itinerary_labels', []);
        $files = $request->file('sample_itinerary_pdfs', []);

        foreach ($labels as $index => $labelRaw) {
            if (count($items) >= 2) {
                break;
            }

            $label = trim((string) $labelRaw);
            $file = is_array($files) ? ($files[$index] ?? null) : null;

            if ($label === '' && ! $file) {
                continue;
            }

            if ($label === '') {
                continue;
            }

            if (! $file || ! $file->isValid()) {
                continue;
            }

            $items[] = [
                'label' => $label,
                'file_path' => $store->store($file, $label),
            ];
        }

        return HajiPlusPage::normalizeSampleItineraries($items);
    }

    private function syncItineraryVisibility(Request $request): void
    {
        $visibleIds = array_map('intval', $request->input('itinerary_visible_departures', []));

        Departure::query()
            ->where('program_kind', 'haji')
            ->where('source', Departure::SOURCE_HAJI_PAGE)
            ->update(['show_on_haji_page' => false]);

        if ($visibleIds === []) {
            return;
        }

        Departure::query()
            ->where('program_kind', 'haji')
            ->where('source', Departure::SOURCE_HAJI_PAGE)
            ->whereIn('id', $visibleIds)
            ->update(['show_on_haji_page' => true]);
    }

    /**
     * @param  array<string, mixed>  $currentHero
     * @param  array<string, mixed>  $heroInput
     * @return array<string, mixed>
     */
    private function syncHeroImages(
        Request $request,
        PackageImageStore $images,
        array $currentHero,
        array $heroInput,
    ): array {
        $defaultImage = HajiPlusPage::defaultHeroImage();
        $library = array_values(array_unique(array_filter(
            is_array($currentHero['images'] ?? null) ? $currentHero['images'] : [],
            fn ($path) => is_string($path) && str_starts_with($path, '/storage/haji-plus/'),
        )));

        $legacyActive = trim((string) ($currentHero['image'] ?? ''));
        if ($library === [] && str_starts_with($legacyActive, '/storage/haji-plus/')) {
            $library = [$legacyActive];
        }

        foreach ($request->input('delete_hero_images', []) as $path) {
            if (! is_string($path) || ! str_starts_with($path, '/storage/haji-plus/')) {
                continue;
            }

            $library = array_values(array_filter($library, fn (string $item) => $item !== $path));
            $this->deleteStoredImage($path);
        }

        if ($request->hasFile('hero.image_file')) {
            $library[] = $images->store($request->file('hero.image_file'), 'haji-hero', 'haji-plus');
            $library = array_values(array_unique($library));
        }

        $selected = trim((string) ($heroInput['image'] ?? ''));
        if ($selected === $defaultImage) {
            $active = $defaultImage;
        } elseif (in_array($selected, $library, true)) {
            $active = $selected;
        } else {
            $previousActive = trim((string) ($currentHero['image'] ?? $defaultImage));
            if ($previousActive === $defaultImage || in_array($previousActive, $library, true)) {
                $active = $previousActive;
            } else {
                $active = $library[0] ?? $defaultImage;
            }
        }

        if ($active !== $defaultImage && ! in_array($active, $library, true)) {
            $active = $library[0] ?? $defaultImage;
        }

        return HajiPlusPage::normalizeHero([
            ...$heroInput,
            'image' => $active,
            'images' => $library,
        ]);
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
