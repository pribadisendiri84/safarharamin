<?php

namespace App\Http\Controllers;

use App\Models\GalleryItem;
use App\Models\Package;
use App\Models\Testimonial;
use App\Support\HajiExchangeRate;

class PageController extends Controller
{
    public function haji()
    {
        $samples = Package::query()
            ->published()
            ->whereIn('type', Package::HAJI_TYPES)
            ->with('packageKind')
            ->orderBy('price')
            ->get();

        return view('pages.haji', [
            'samples' => $samples,
            'hajiExchangeRate' => HajiExchangeRate::display(),
        ]);
    }

    public function about()
    {
        return view('pages.about');
    }

    public function gallery()
    {
        $activeCategory = request()->string('kategori')->toString();
        if (! array_key_exists($activeCategory, GalleryItem::categories())) {
            $activeCategory = GalleryItem::CATEGORY_UMROH;
        }

        return view('pages.gallery', [
            'grouped' => GalleryItem::groupedForStorefront(),
            'categories' => GalleryItem::categories(),
            'activeCategory' => $activeCategory,
        ]);
    }

    public function testimonials()
    {
        return view('pages.testimonials', [
            'testimonials' => Testimonial::query()->published()->get(),
        ]);
    }
}
