<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Package;
use App\Models\VisitorEvent;
use App\Support\VisitorTracker;
use Illuminate\Database\Eloquent\Builder;

class DashboardController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $visible = Inquiry::query()->visibleTo($user);
        $sold = (clone $visible)->where('status', Inquiry::STATUS_SOLD);

        $funnel = [];
        if ($user?->canSeeLeadSources()) {
            $webDaftar = Inquiry::query()->where('source', Inquiry::SOURCE_WEBSITE)->where('kind', 'daftar');
            $webTanya = Inquiry::query()->where('source', Inquiry::SOURCE_WEBSITE)->where('kind', 'tanya');
            $team = Inquiry::query()->where('source', Inquiry::SOURCE_TEAM);
            $funnel = [
                'web_daftar' => (clone $webDaftar)->count(),
                'web_daftar_closing' => $this->closingCount($webDaftar),
                'web_tanya' => (clone $webTanya)->count(),
                'web_tanya_closing' => $this->closingCount($webTanya),
                'tim' => (clone $team)->count(),
                'tim_closing' => $this->closingCount($team),
            ];
        }

        return view('admin.dashboard', [
            'published' => Package::query()->published()->count(),
            'pipeline' => (clone $visible)->whereIn('status', Inquiry::OPEN_STATUSES)->count(),
            'inquiries' => (clone $visible)->where('status', Inquiry::STATUS_NEW)->count(),
            'soldPax' => (int) (clone $sold)->sum('sold_pax'),
            'soldAmount' => (int) (clone $sold)->sum('sold_amount'),
            'soldCount' => (clone $sold)->count(),
            'funnel' => $funnel,
            'latestInquiries' => (clone $visible)
                ->with(['package', 'pic'])
                ->whereIn('status', Inquiry::OPEN_STATUSES)
                ->latest()
                ->limit(6)
                ->get(),
            'latestSales' => (clone $visible)
                ->with(['package', 'pic'])
                ->where('status', Inquiry::STATUS_SOLD)
                ->latest('closed_at')
                ->limit(6)
                ->get(),
            'traffic' => $user?->isSuperadmin() ? $this->trafficSnapshot() : null,
        ]);
    }

    private function closingCount(Builder $query): int
    {
        return (clone $query)->where('status', Inquiry::STATUS_SOLD)->count();
    }

    /**
     * @return array{visitors_today: int, views_7d: int, wa_clicks_7d: int, wa_rate_7d: string}|null
     */
    private function trafficSnapshot(): ?array
    {
        if (! VisitorTracker::tableReady()) {
            return null;
        }

        try {
            $today = now()->startOfDay();
            $week = now()->subDays(7)->startOfDay();
            $visitorsWeek = VisitorEvent::uniqueVisitors($week);
            $waVisitorsWeek = VisitorEvent::uniqueWaClickers($week);

            return [
                'visitors_today' => VisitorEvent::uniqueVisitors($today),
                'views_7d' => VisitorEvent::query()->pageViews()->since($week)->count(),
                'wa_clicks_7d' => VisitorEvent::query()->waClicks()->since($week)->count(),
                'wa_rate_7d' => $visitorsWeek === 0
                    ? '0%'
                    : number_format(($waVisitorsWeek / $visitorsWeek) * 100, 1, ',', '.').'%',
            ];
        } catch (\Throwable) {
            VisitorTracker::flushReadyState();

            return null;
        }
    }
}
