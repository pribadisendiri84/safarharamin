<?php

namespace App\Http\Middleware;

use App\Support\VisitorTracker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordPageView
{
    public const REQUEST_FLAG = '_safarharamin_record_page_view';

    public function __construct(private VisitorTracker $tracker) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->tracker->shouldIgnore($request)) {
            $this->tracker->rememberVisit($request);
            $request->attributes->set(self::REQUEST_FLAG, true);
        }

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $request->attributes->get(self::REQUEST_FLAG) || $response->getStatusCode() >= 400) {
            return;
        }

        $this->tracker->recordPageView($request, queueCookies: false);
    }
}
