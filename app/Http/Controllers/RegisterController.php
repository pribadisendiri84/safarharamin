<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Package;
use App\Support\HajiPlusProgram;
use App\Support\WaMessages;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    public function create()
    {
        $program = request()->string('program')->toString();
        $room = trim(request()->string('room')->toString());
        $hajiPackage = $program === 'haji' ? HajiPlusProgram::primary() : null;
        $selectedPackageId = request('package_id') ?: $hajiPackage?->id;
        $defaultNotes = ($program === 'haji' && $room !== '')
            ? "Minat program Haji Plus — tipe kamar {$room}."
            : ($program === 'haji' ? 'Minat program Haji Plus.' : '');

        return view('register', [
            'packages' => Package::query()->published()->orderBy('departure_date')->get(),
            'selectedPackageId' => $selectedPackageId,
            'defaultNotes' => $defaultNotes,
            'isHajiProgram' => $program === 'haji',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'city' => ['required', 'string', Rule::exists('cities', 'slug')->whereNull('deleted_at')],
            'package_id' => ['nullable', 'exists:packages,id'],
            'pax' => ['required', 'integer', 'min:1', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $inquiry = Inquiry::query()->create([
            ...$data,
            'kind' => 'daftar',
            'source' => Inquiry::SOURCE_WEBSITE,
            'status' => 'baru',
        ]);

        $message = WaMessages::register($inquiry, $inquiry->package);

        $request->session()->put('wa_text', $message);

        return redirect()
            ->route('register')
            ->with('ok', 'Pendaftaran tercatat. Tim kami akan menghubungi Anda.')
            ->with('registration_success', true)
            ->with('wa_url', route('go.whatsapp', ['from' => 'form']))
            ->with('feedback_title', 'Pendaftaran berhasil')
            ->with('feedback_action_url', route('go.whatsapp', ['from' => 'form']))
            ->with('feedback_action_label', 'Lanjut ke WhatsApp')
            ->with('feedback_action_target', '_blank');
    }
}
