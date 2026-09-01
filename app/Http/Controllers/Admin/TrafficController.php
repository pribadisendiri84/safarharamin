<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\VisitorEvent;
use App\Support\VisitorTracker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TrafficController extends Controller
{
    public function index()
    {
        $today = now()->startOfDay();
        $week = now()->subDays(7)->startOfDay();
        $month = now()->subDays(30)->startOfDay();
        $ready = VisitorTracker::tableReady();

        try {
            return view('admin.traffic.index', [
                'periods' => [
                    'today' => $this->periodStats($today, 'Hari ini', $ready),
                    'week' => $this->periodStats($week, '7 hari', $ready),
                    'month' => $this->periodStats($month, '30 hari', $ready),
                ],
                'waByPlacement' => $ready ? $this->waByPlacement($week) : $this->emptyPlacements(),
                'sources' => $ready ? $this->topSources($month) : collect(),
                'pages' => $ready ? $this->topPages($month) : collect(),
                'dailyClicks' => $ready ? $this->dailyWaClicks($month) : collect(),
            ]);
        } catch (\Throwable) {
            VisitorTracker::flushReadyState();

            return view('admin.traffic.index', [
                'periods' => [
                    'today' => $this->periodStats($today, 'Hari ini', false),
                    'week' => $this->periodStats($week, '7 hari', false),
                    'month' => $this->periodStats($month, '30 hari', false),
                ],
                'waByPlacement' => $this->emptyPlacements(),
                'sources' => collect(),
                'pages' => collect(),
                'dailyClicks' => collect(),
            ]);
        }
    }

    /**
     * @return array{label: string, visitors: int, views: int, wa_clicks: int, wa_visitors: int, leads: int, wa_rate: string, lead_rate: string}
     */
    private function periodStats(Carbon $from, string $label, bool $ready): array
    {
        $visitors = $ready ? VisitorEvent::uniqueVisitors($from) : 0;
        $views = $ready ? VisitorEvent::query()->pageViews()->since($from)->count() : 0;
        $waClicks = $ready ? VisitorEvent::query()->waClicks()->since($from)->count() : 0;
        $waVisitors = $ready ? VisitorEvent::uniqueWaClickers($from) : 0;
        $leads = Inquiry::query()
            ->where('source', Inquiry::SOURCE_WEBSITE)
            ->where('created_at', '>=', $from)
            ->count();

        return [
            'label' => $label,
            'visitors' => $visitors,
            'views' => $views,
            'wa_clicks' => $waClicks,
            'wa_visitors' => $waVisitors,
            'leads' => $leads,
            'wa_rate' => $this->rate($waVisitors, $visitors),
            'lead_rate' => $this->rate($leads, $visitors),
        ];
    }

    /**
     * @return Collection<int, object{wa_placement: string, label: string, clicks: int}>
     */
    private function emptyPlacements(): Collection
    {
        return collect(VisitorEvent::PLACEMENTS)->map(fn (string $label, string $key) => (object) [
            'wa_placement' => $key,
            'label' => $label,
            'clicks' => 0,
        ]);
    }

    /**
     * @return Collection<int, object{wa_placement: ?string, clicks: int}>
     */
    private function waByPlacement(Carbon $from): Collection
    {
        $counts = VisitorEvent::query()
            ->waClicks()
            ->since($from)
            ->selectRaw('wa_placement, count(*) as clicks')
            ->groupBy('wa_placement')
            ->pluck('clicks', 'wa_placement');

        return collect(VisitorEvent::PLACEMENTS)->map(fn (string $label, string $key) => (object) [
            'wa_placement' => $key,
            'label' => $label,
            'clicks' => (int) ($counts[$key] ?? 0),
        ]);
    }

    /**
     * @return Collection<int, object>
     */
    private function topSources(Carbon $from): Collection
    {
        return VisitorEvent::query()
            ->pageViews()
            ->since($from)
            ->selectRaw("case
                when utm_source is not null and utm_source != '' then utm_source
                when referrer_host is not null and referrer_host != '' then referrer_host
                else 'Langsung'
            end as source, count(*) as views, count(distinct session_id) as visitors")
            ->groupBy('source')
            ->orderByDesc('visitors')
            ->limit(15)
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function topPages(Carbon $from): Collection
    {
        return VisitorEvent::query()
            ->pageViews()
            ->since($from)
            ->selectRaw('path, count(*) as views, count(distinct session_id) as visitors')
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(15)
            ->get();
    }

    /**
     * @return Collection<int, object>
     */
    private function dailyWaClicks(Carbon $from): Collection
    {
        return VisitorEvent::query()
            ->waClicks()
            ->since($from)
            ->selectRaw('date(created_at) as day, count(*) as clicks, count(distinct session_id) as visitors')
            ->groupBy('day')
            ->orderBy('day')
            ->get();
    }

    private function rate(int $part, int $whole): string
    {
        if ($whole === 0) {
            return '0%';
        }

        return number_format(($part / $whole) * 100, 1, ',', '.').'%';
    }
}
