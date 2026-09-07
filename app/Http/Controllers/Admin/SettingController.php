<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\PackageImageStore;
use App\Support\HajiExchangeRate;
use App\Support\SiteProfile;
use App\Support\WaMessages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', [
            'site' => SiteProfile::current(),
            'waMessages' => WaMessages::adminTemplates(),
            'hajiExchangeRate' => HajiExchangeRate::adminForm(),
        ]);
    }

    public function update(Request $request, PackageImageStore $images)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_tagline' => ['required', 'string', 'max:300'],
            'site_title_suffix' => ['required', 'string', 'max:80'],
            'package_card_style' => ['required', Rule::in(array_keys(SiteProfile::CARD_STYLES))],
            'wa_number' => ['required', 'string', 'max:20'],
            'wa_float_enabled' => ['nullable', 'boolean'],
            'wa_float_label' => ['required', 'string', 'max:80'],
            'wa_msg_header' => ['required', 'string', 'max:2000'],
            'wa_msg_float' => ['required', 'string', 'max:2000'],
            'wa_msg_package' => ['required', 'string', 'max:2000'],
            'wa_msg_register' => ['required', 'string', 'max:2000'],
            'wa_msg_inquiry_reply' => ['required', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'haji_exchange_rate_enabled' => ['nullable', 'boolean'],
            'haji_exchange_rate_mode' => ['required', Rule::in([HajiExchangeRate::MODE_MANUAL, HajiExchangeRate::MODE_AUTO])],
            'haji_exchange_rate_currency' => ['required', 'string', 'max:8'],
            'haji_exchange_rate' => ['nullable', 'integer', 'min:1', 'max:99999999'],
        ]);

        Setting::setValue('site_name', trim($data['site_name']));
        Setting::setValue('site_tagline', trim($data['site_tagline']));
        Setting::setValue('site_title_suffix', trim($data['site_title_suffix']));
        Setting::setValue('package_card_style', $data['package_card_style']);
        Setting::setValue('wa_number', preg_replace('/\D+/', '', $data['wa_number']) ?? '');
        Setting::setValue(WaMessages::KEY_FLOAT_ENABLED, $request->boolean('wa_float_enabled') ? '1' : '0');
        Setting::setValue(WaMessages::KEY_FLOAT_LABEL, trim($data['wa_float_label']));
        Setting::setValue(WaMessages::KEY_HEADER, trim($data['wa_msg_header']));
        Setting::setValue(WaMessages::KEY_FLOAT, trim($data['wa_msg_float']));
        Setting::setValue(WaMessages::KEY_PACKAGE, trim($data['wa_msg_package']));
        Setting::setValue(WaMessages::KEY_REGISTER, trim($data['wa_msg_register']));
        Setting::setValue(WaMessages::KEY_INQUIRY_REPLY, trim($data['wa_msg_inquiry_reply']));

        if ($request->hasFile('logo')) {
            $previous = Setting::getValue('site_logo');
            $path = $images->store($request->file('logo'), $data['site_name'], 'brand');
            Setting::setValue('site_logo', $path);
            $this->deleteStoredLogo($previous);
        }

        $enabled = $request->boolean('haji_exchange_rate_enabled');
        $mode = $data['haji_exchange_rate_mode'];
        $currency = strtoupper(trim($data['haji_exchange_rate_currency']));
        $manualRate = (int) ($data['haji_exchange_rate'] ?? 0);

        if ($mode === HajiExchangeRate::MODE_MANUAL && $enabled && $manualRate <= 0) {
            return back()
                ->withInput()
                ->withErrors(['haji_exchange_rate' => 'Isi nilai kurs untuk mode manual.']);
        }

        HajiExchangeRate::saveManual(
            $manualRate > 0 ? $manualRate : (HajiExchangeRate::storedRate() ?? 0),
            $currency,
            $enabled,
            $mode
        );

        if ($enabled && $mode === HajiExchangeRate::MODE_AUTO) {
            HajiExchangeRate::refreshFromApi();
        }

        return redirect()->route('admin.settings.edit')->with('ok', 'Pengaturan tersimpan.');
    }

    public function refreshExchangeRate()
    {
        if (! HajiExchangeRate::enabled() || HajiExchangeRate::mode() !== HajiExchangeRate::MODE_AUTO) {
            return redirect()->route('admin.settings.edit')->with('err', 'Mode otomatis belum aktif.');
        }

        if (! HajiExchangeRate::refreshFromApi()) {
            return redirect()->route('admin.settings.edit')->with('err', 'Gagal memperbarui kurs. Coba lagi nanti atau gunakan input manual.');
        }

        return redirect()->route('admin.settings.edit')->with('ok', 'Kurs diperbarui dari sumber otomatis.');
    }

    private function deleteStoredLogo(string $path): void
    {
        if ($path === '' || ! str_starts_with($path, '/storage/')) {
            return;
        }

        Storage::disk('public')->delete(ltrim(substr($path, strlen('/storage/')), '/'));
    }
}
