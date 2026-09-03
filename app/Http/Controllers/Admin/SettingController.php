<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\PackageImageStore;
use App\Support\SiteProfile;
use App\Support\WaMessages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', [
            'site' => SiteProfile::current(),
            'waMessages' => WaMessages::adminTemplates(),
        ]);
    }

    public function update(Request $request, PackageImageStore $images)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_tagline' => ['required', 'string', 'max:300'],
            'site_title_suffix' => ['required', 'string', 'max:80'],
            'wa_number' => ['required', 'string', 'max:20'],
            'wa_float_enabled' => ['nullable', 'boolean'],
            'wa_float_label' => ['required', 'string', 'max:80'],
            'wa_msg_float' => ['required', 'string', 'max:2000'],
            'wa_msg_package' => ['required', 'string', 'max:2000'],
            'wa_msg_register' => ['required', 'string', 'max:2000'],
            'wa_msg_inquiry_reply' => ['required', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        Setting::setValue('site_name', trim($data['site_name']));
        Setting::setValue('site_tagline', trim($data['site_tagline']));
        Setting::setValue('site_title_suffix', trim($data['site_title_suffix']));
        Setting::setValue('wa_number', preg_replace('/\D+/', '', $data['wa_number']) ?? '');
        Setting::setValue(WaMessages::KEY_FLOAT_ENABLED, $request->boolean('wa_float_enabled') ? '1' : '0');
        Setting::setValue(WaMessages::KEY_FLOAT_LABEL, trim($data['wa_float_label']));
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

        return redirect()->route('admin.settings.edit')->with('ok', 'Pengaturan tersimpan.');
    }

    private function deleteStoredLogo(string $path): void
    {
        if ($path === '' || ! str_starts_with($path, '/storage/')) {
            return;
        }

        Storage::disk('public')->delete(ltrim(substr($path, strlen('/storage/')), '/'));
    }
}
