<?php

namespace App\Http\Controllers;

use App\Models\GalleryItem;
use App\Models\Testimonial;
use App\Support\HajiExchangeRate;
use App\Support\HajiPlusPage;
use App\Support\HajiPlusProgram;

class PageController extends Controller
{
    public function hajiKhusus()
    {
        $program = HajiPlusProgram::primary();

        return view('pages.haji-khusus', [
            'program' => $program,
            'page' => HajiPlusPage::content($program),
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
