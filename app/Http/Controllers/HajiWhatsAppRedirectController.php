<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Support\HajiPlusProgram;
use App\Support\SiteProfile;
use App\Support\VisitorTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HajiWhatsAppRedirectController extends Controller
{
    public function __invoke(Request $request, VisitorTracker $tracker): RedirectResponse
    {
        $intent = $request->string('intent')->toString();
        if (! in_array($intent, ['consult', 'room'], true)) {
            $intent = 'consult';
        }

        $room = trim($request->string('room')->toString());
        $package = HajiPlusProgram::primary();

        $notes = $intent === 'room' && $room !== ''
            ? "Haji Plus — minat tipe kamar {$room} (klik dari halaman Haji)"
            : 'Haji Plus — konsultasi umum (klik WhatsApp dari halaman Haji)';

        Inquiry::query()->create([
            'kind' => 'tanya',
            'source' => Inquiry::SOURCE_WEBSITE,
            'name' => 'Pengunjung Haji',
            'phone' => '—',
            'package_id' => $package?->id,
            'notes' => $notes,
            'status' => Inquiry::STATUS_NEW,
            'pax' => 1,
        ]);

        $tracker->recordWaClick($request, 'haji');

        $message = $intent === 'room' && $room !== ''
            ? HajiPlusProgram::roomConsultationMessage($room)
            : HajiPlusProgram::consultationMessage();

        $number = preg_replace('/\D+/', '', SiteProfile::current()->waNumber) ?: SiteProfile::DEFAULT_WA;

        return redirect()->away('https://wa.me/'.$number.'?text='.rawurlencode($message));
    }
}
