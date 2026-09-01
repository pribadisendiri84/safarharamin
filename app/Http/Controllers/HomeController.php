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
            ->published()
            ->orderByDesc('is_hot')
            ->orderByDesc('is_featured')
            ->orderBy('price')
            ->limit(6)
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
            'gallery' => GalleryItem::query()->orderBy('sort_order')->limit(6)->get(),
        ]);
    }
}
