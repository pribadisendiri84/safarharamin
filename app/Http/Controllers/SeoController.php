<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => route('packages.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => route('price-list'), 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => route('haji'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => route('about'), 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => route('gallery'), 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['loc' => route('testimonials'), 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => route('register'), 'priority' => '0.7', 'changefreq' => 'monthly'],
        ];

        foreach (Package::query()->publiclyVisible()->orderByDesc('updated_at')->get(['slug', 'updated_at']) as $package) {
            $urls[] = [
                'loc' => route('packages.show', $package),
                'priority' => '0.8',
                'changefreq' => 'weekly',
                'lastmod' => $package->updated_at?->toAtomString(),
            ];
        }

        return response()
            ->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /go/',
            '',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
