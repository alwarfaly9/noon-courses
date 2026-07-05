<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'platform_name'     => Setting::get('platform_name', 'EdLibya'),
            'platform_logo_url' => Setting::get('platform_logo_url', ''),
        ];

        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'platform_name' => 'required|string|max:100',
            'logo_file'     => 'nullable|file|mimes:jpg,jpeg,png,svg|max:2048',
        ]);

        Setting::set('platform_name', $request->platform_name, 'text');

        if ($request->hasFile('logo_file')) {
            // Delete old logo if stored locally
            $oldUrl = Setting::get('platform_logo_url');
            if ($oldUrl && str_contains($oldUrl, '/storage/settings/')) {
                Storage::disk('public')->delete(
                    str_replace(url('storage/'), '', $oldUrl)
                );
            }

            $path = $request->file('logo_file')->store('settings', 'public');
            Setting::set('platform_logo_url', asset('storage/' . $path), 'text');
        }

        return redirect()->back()->with('success', 'تم حفظ الإعدادات بنجاح');
    }
}
