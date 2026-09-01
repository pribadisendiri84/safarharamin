<?php

namespace App\Http\Controllers;

use App\Support\SiteProfile;
use App\Support\VisitorTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WhatsAppRedirectController extends Controller
{
    public function __invoke(Request $request, VisitorTracker $tracker): RedirectResponse
    {
        $from = $request->string('from')->toString();
        if (! in_array($from, VisitorTracker::PLACEMENTS, true)) {
            $from = 'header';
        }

        $tracker->recordWaClick($request, $from);

        $number = preg_replace('/\D+/', '', SiteProfile::current()->waNumber) ?: SiteProfile::DEFAULT_WA;
        $text = match ($from) {
            'float' => 'Halo '.SiteProfile::current()->name.', saya ingin tanya paket haji/umroh.',
            'form' => (string) $request->session()->pull('wa_text', ''),
            default => '',
        };

        $url = 'https://wa.me/'.$number;
        if ($text !== '') {
            $url .= '?text='.rawurlencode($text);
        }

        return redirect()->away($url);
    }
}
