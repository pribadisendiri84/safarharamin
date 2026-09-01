<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Inquiry;
use App\Models\Package;
use App\Support\SiteProfile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    public function create()
    {
        return view('register', [
            'packages' => Package::query()->published()->orderBy('departure_date')->get(),
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

        $package = $inquiry->package;
        $message = 'Halo '.SiteProfile::current()->name.', saya '.$inquiry->name.' ingin daftar'.
            ($package ? ' paket '.$package->title : '').
            ' ('.$inquiry->pax.' jamaah) dari '.City::label($inquiry->city).
            '. Mohon dihubungi.';

        $request->session()->put('wa_text', $message);

        return redirect()
            ->route('register')
            ->with('ok', 'Pendaftaran tercatat. Tim kami akan menghubungi Anda.')
            ->with('wa_url', route('go.whatsapp', ['from' => 'form']));
    }
}
