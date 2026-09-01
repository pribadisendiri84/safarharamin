<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\PackageImageStore;
use App\Support\SiteProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit()
    {
        return view('admin.settings', [
            'site' => SiteProfile::current(),
        ]);
    }

    public function update(Request $request, PackageImageStore $images)
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'site_tagline' => ['required', 'string', 'max:300'],
            'site_title_suffix' => ['required', 'string', 'max:80'],
            'wa_number' => ['required', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        Setting::setValue('site_name', trim($data['site_name']));
        Setting::setValue('site_tagline', trim($data['site_tagline']));
        Setting::setValue('site_title_suffix', trim($data['site_title_suffix']));
        Setting::setValue('wa_number', preg_replace('/\D+/', '', $data['wa_number']) ?? '');

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
