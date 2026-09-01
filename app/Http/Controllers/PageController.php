<?php

namespace App\Http\Controllers;

use App\Models\GalleryItem;
use App\Models\Package;
use App\Models\Testimonial;

class PageController extends Controller
{
    public function haji()
    {
        $samples = Package::query()
            ->published()
            ->whereIn('type', ['haji_plus', 'haji_furoda'])
            ->orderBy('price')
            ->get();

        return view('pages.haji', ['samples' => $samples]);
    }

    public function gallery()
    {
        return view('pages.gallery', [
            'items' => GalleryItem::query()->orderBy('sort_order')->orderByDesc('id')->get(),
        ]);
    }

    public function testimonials()
    {
        return view('pages.testimonials', [
            'testimonials' => Testimonial::query()->published()->get(),
        ]);
    }
}
