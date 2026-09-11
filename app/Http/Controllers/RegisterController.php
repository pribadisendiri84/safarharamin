<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Package;
use App\Support\WaMessages;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    public function create()
    {
        $program = request()->string('program')->toString();
        $room = trim(request()->string('room')->toString());
        $isHaji = $program === 'haji';
        $defaultNotes = ($isHaji && $room !== '')
            ? "Minat program Haji Plus — tipe kamar {$room}."
            : ($isHaji ? 'Minat program Haji Plus.' : '');

        return view('register', [
            'packages' => Package::query()
                ->publiclyVisible()
                ->when($isHaji, fn ($query) => $query->whereNotIn('type', Package::HAJI_TYPES))
                ->orderBy('departure_date')
                ->get(),
            'selectedPackageId' => $isHaji ? null : request('package_id'),
            'defaultNotes' => $defaultNotes,
            'isHajiProgram' => $isHaji,
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
            'program_kind' => ['nullable', Rule::in(['haji'])],
            'pax' => ['required', 'integer', 'min:1', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (($data['program_kind'] ?? null) === 'haji') {
            $data['package_id'] = null;
        }

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
