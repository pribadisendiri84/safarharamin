<?php

namespace App\Http\Controllers;

use App\Models\GalleryItem;
use App\Models\Package;
use App\Models\Testimonial;

class HomeController extends Controller
{
    public function index()
    {
        $featured = Package::query()
            ->displayedOnHome()
            ->with('packageKind')
            ->orderBy('home_sort')
            ->orderByDesc('id')
            ->limit(Package::homeLimit())
            ->get();

        $counts = Package::query()
            ->published()
            ->selectRaw('departure_city, count(*) as total')
            ->groupBy('departure_city')
            ->pluck('total', 'departure_city');

        $umrohCount = Package::query()->published()->whereIn('type', Package::UMROH_TYPES)->count();
        $hajiCount = Package::query()->published()->whereIn('type', Package::HAJI_TYPES)->count();

        return view('home', [
            'featured' => $featured,
            'counts' => $counts,
            'total' => $counts->sum(),
            'umrohCount' => $umrohCount,
            'hajiCount' => $hajiCount,
            'testimonials' => Testimonial::query()->published()->limit(3)->get(),
            'gallery' => GalleryItem::query()->displayedOnHome()->orderBy('home_sort')->orderByDesc('id')->limit(GalleryItem::homeLimit())->get(),
        ]);
    }
}
