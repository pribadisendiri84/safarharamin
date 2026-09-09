<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Support\CompanyProfile;
use App\Support\HomeSeo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_has_brand_focused_seo_meta_without_changing_visible_hero(): void
    {
        $response = $this->get('/')->assertOk();

        $html = $response->getContent();

        $response->assertSee(
            '<title>'.HomeSeo::pageTitle().' —',
            false
        );
        $response->assertSee(
            'meta name="description" content="'.e(HomeSeo::metaDescription()).'"',
            false
        );
        $response->assertSee('rel="canonical"', false);
        $this->assertStringContainsString(route('home'), $html);
        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('application/ld+json', false);
        $response->assertSee(CompanyProfile::BRAND_NAME, false);
        $response->assertSee(CompanyProfile::LEGAL_NAME, false);
        $this->assertStringContainsString('"@type":"Organization"', $html);
        $this->assertStringContainsString('"@type":"WebSite"', $html);

        $response->assertSee('Perjalanan spiritual Anda dimulai di sini', false);
        $this->assertSame(1, substr_count($html, '<h1>'));
        $this->assertStringContainsString('<h1>Perjalanan spiritual Anda dimulai di sini</h1>', $html);
    }

    public function test_sitemap_lists_homepage_first_and_includes_storefront_routes(): void
    {
        Package::query()->create([
            'title' => 'Umroh SEO Test',
            'slug' => 'umroh-seo-test',
            'type' => 'umroh',
            'package_kind_id' => $this->packageKindId(),
            'departure_city' => 'jakarta',
            'departure_date' => '2026-10-12',
            'duration_days' => 9,
            'price' => 29500000,
            'price_quad' => 29500000,
            'seats_total' => 40,
            'seats_left' => 12,
            'status' => 'published',
            'images' => ['/images/placeholder-kaaba.svg'],
        ]);

        $xml = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->getContent();

        $this->assertStringContainsString(route('home'), $xml);
        $this->assertStringContainsString('<priority>1.0</priority>', $xml);
        $this->assertStringContainsString(route('about', absolute: false), $xml);
        $this->assertStringContainsString('/paket/umroh-seo-test', $xml);
        $this->assertLessThan(
            strpos($xml, route('about', absolute: false)),
            strpos($xml, route('home'))
        );
    }

    public function test_robots_txt_disallows_admin_and_points_to_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /go/')
            ->assertSee('Sitemap: '.route('sitemap'));
    }
}
