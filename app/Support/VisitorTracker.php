<?php

namespace App\Support;

use App\Models\VisitorEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class VisitorTracker
{
    public const COOKIE_ID = 'sh_vid';

    public const COOKIE_SRC = 'sh_src';

    public const COOKIE_MINUTES = 60 * 24 * 30;

    public const PLACEMENTS = ['header', 'float', 'form'];

    private static ?bool $tableReady = null;

    public static function tableReady(): bool
    {
        if (self::$tableReady !== null) {
            return self::$tableReady;
        }

        try {
            return self::$tableReady = Schema::hasTable('visitor_events');
        } catch (\Throwable) {
            return self::$tableReady = false;
        }
    }

    public static function flushReadyState(): void
    {
        self::$tableReady = null;
    }

    public function rememberVisit(Request $request): void
    {
        if (! self::tableReady()) {
            return;
        }

        $sessionId = $this->sessionId($request);
        $touch = $this->firstTouch($request);
        $this->queueCookies($sessionId, $touch);
        $request->cookies->set(self::COOKIE_ID, $sessionId);
        $request->cookies->set(self::COOKIE_SRC, json_encode($touch, JSON_UNESCAPED_SLASHES));
    }

    public function recordPageView(Request $request, bool $queueCookies = true): ?VisitorEvent
    {
        return $this->record($request, VisitorEvent::TYPE_PAGE_VIEW, queueCookies: $queueCookies);
    }

    public function recordWaClick(Request $request, string $placement): ?VisitorEvent
    {
        if ($this->isBot($request)) {
            return null;
        }

        $placement = in_array($placement, self::PLACEMENTS, true) ? $placement : 'header';

        return $this->record($request, VisitorEvent::TYPE_WA_CLICK, $placement);
    }

    public function shouldIgnore(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return true;
        }

        if ($request->is('admin', 'admin/*', 'go', 'go/*', 'up')) {
            return true;
        }

        if ($request->ajax() || $request->prefetch() || $request->expectsJson()) {
            return true;
        }

        return $this->isBot($request);
    }

    private function record(Request $request, string $type, ?string $placement = null, bool $queueCookies = true): ?VisitorEvent
    {
        if (! self::tableReady()) {
            return null;
        }

        $sessionId = $this->sessionId($request);
        $touch = $this->firstTouch($request);

        if ($queueCookies) {
            $this->queueCookies($sessionId, $touch);
        }

        try {
            return VisitorEvent::query()->create([
                'type' => $type,
                'session_id' => $sessionId,
                'path' => $type === VisitorEvent::TYPE_WA_CLICK
                    ? $this->clickPath($request)
                    : $this->currentPath($request),
                'landing_path' => $touch['landing_path'],
                'referrer_host' => $touch['referrer_host'],
                'utm_source' => $touch['utm_source'],
                'utm_medium' => $touch['utm_medium'],
                'utm_campaign' => $touch['utm_campaign'],
                'wa_placement' => $placement,
                'created_at' => now(),
            ]);
        } catch (\Throwable) {
            return null;
        }
    }

    private function sessionId(Request $request): string
    {
        $id = (string) $request->cookie(self::COOKIE_ID);

        return Str::isUuid($id) ? $id : (string) Str::uuid();
    }

    /**
     * @return array{landing_path: ?string, referrer_host: ?string, utm_source: ?string, utm_medium: ?string, utm_campaign: ?string}
     */
    private function firstTouch(Request $request): array
    {
        $saved = $this->decodeTouch((string) $request->cookie(self::COOKIE_SRC));
        if ($saved) {
            return $saved;
        }

        return [
            'landing_path' => $this->currentPath($request),
            'referrer_host' => $this->externalReferrerHost($request),
            'utm_source' => $this->utm($request, 'utm_source'),
            'utm_medium' => $this->utm($request, 'utm_medium'),
            'utm_campaign' => $this->utm($request, 'utm_campaign'),
        ];
    }

    /**
     * @param  array{landing_path: ?string, referrer_host: ?string, utm_source: ?string, utm_medium: ?string, utm_campaign: ?string}  $touch
     */
    private function queueCookies(string $sessionId, array $touch): void
    {
        Cookie::queue(cookie(self::COOKIE_ID, $sessionId, self::COOKIE_MINUTES));
        Cookie::queue(cookie(self::COOKIE_SRC, json_encode($touch, JSON_UNESCAPED_SLASHES), self::COOKIE_MINUTES));
    }

    /**
     * @return array{landing_path: ?string, referrer_host: ?string, utm_source: ?string, utm_medium: ?string, utm_campaign: ?string}|null
     */
    private function decodeTouch(string $raw): ?array
    {
        if ($raw === '') {
            return null;
        }

        $data = json_decode($raw, true);
        if (! is_array($data) || ! array_key_exists('landing_path', $data)) {
            return null;
        }

        return [
            'landing_path' => $this->nullableString($data['landing_path'] ?? null),
            'referrer_host' => $this->nullableString($data['referrer_host'] ?? null),
            'utm_source' => $this->nullableString($data['utm_source'] ?? null),
            'utm_medium' => $this->nullableString($data['utm_medium'] ?? null),
            'utm_campaign' => $this->nullableString($data['utm_campaign'] ?? null),
        ];
    }

    private function currentPath(Request $request): string
    {
        $path = $request->path();
        $path = $path === '/' ? '/' : '/'.$path;
        $query = collect($request->query())
            ->except(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        if ($query !== []) {
            $path .= '?'.http_build_query($query);
        }

        return Str::limit($path, 255, '');
    }

    private function clickPath(Request $request): ?string
    {
        $referer = $request->headers->get('referer');
        if (! $referer) {
            return $this->currentPath($request);
        }

        $path = parse_url($referer, PHP_URL_PATH) ?: '/';

        return Str::limit($path, 255, '');
    }

    private function externalReferrerHost(Request $request): ?string
    {
        $referer = $request->headers->get('referer');
        if (! $referer) {
            return null;
        }

        $host = parse_url($referer, PHP_URL_HOST);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        if (! $host) {
            return null;
        }

        if ($appHost && strcasecmp($host, $appHost) === 0) {
            return null;
        }

        return Str::limit($host, 120, '');
    }

    private function utm(Request $request, string $key): ?string
    {
        return $this->nullableString($request->query($key));
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : Str::limit($value, 120, '');
    }

    private function isBot(Request $request): bool
    {
        $agent = strtolower((string) $request->userAgent());

        return $agent !== '' && (bool) preg_match(
            '/bot|crawl|spider|slurp|facebookexternalhit|preview|wget|curl|python-requests|httpie/',
            $agent,
        );
    }
}
