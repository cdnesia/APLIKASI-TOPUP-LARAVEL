<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', [
            'settings' => collect(Setting::DEFAULTS)->map(fn ($default, $key) => Setting::get($key)),
            'webhookUrl' => route('webhook.digiflazz'),
            'sitemapUrl' => route('sitemap'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:60'],
            'site_tagline' => ['nullable', 'string', 'max:150'],
            'whatsapp_admin' => ['nullable', 'regex:/^62[0-9]{8,13}$/'],
            'announcement' => ['nullable', 'string', 'max:300'],
            'contact_email' => ['nullable', 'email', 'max:100'],
            'contact_address' => ['nullable', 'string', 'max:300'],
            'instagram' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9._]+$/'],
            'operational_hours' => ['nullable', 'string', 'max:100'],
            'google_site_verification' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'seo_home_title' => ['nullable', 'string', 'max:120'],
            'seo_home_description' => ['nullable', 'string', 'max:300'],
            'seo_brand_title' => ['required', 'string', 'max:120'],
            'seo_brand_description' => ['required', 'string', 'max:300'],
            'margin_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'margin_flat' => ['required', 'integer', 'min:0'],
            'price_rounding' => ['required', 'integer', 'min:1'],
            'order_expiry_hours' => ['required', 'integer', 'min:1', 'max:72'],
            // SVG sengaja tidak diizinkan: file SVG bisa berisi skrip
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'logo_dark' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico', 'max:512'],
            'og_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=600,min_height=315'],
        ], [
            'logo.mimes' => 'Logo harus berformat PNG, JPG, atau WEBP.',
            'logo_dark.mimes' => 'Logo harus berformat PNG, JPG, atau WEBP.',
            'favicon.mimes' => 'Favicon harus berformat PNG atau ICO.',
            'og_image.dimensions' => 'Gambar share minimal 600×315 piksel (disarankan 1200×630).',
            'whatsapp_admin.regex' => 'Gunakan format 62xxx, contoh: 6281234567890',
            'google_site_verification.regex' => 'Isi kodenya saja (isi atribut content), bukan seluruh tag <meta>.',
            'instagram.regex' => 'Isi username saja tanpa @, contoh: elhazmi.store',
        ]);

        foreach (Setting::IMAGES as $key) {
            unset($data[$key]);
            $old = Setting::get($key);

            if ($request->hasFile($key)) {
                $data[$key] = $request->file($key)->store('branding', 'public');
            } elseif ($request->boolean("remove_{$key}")) {
                $data[$key] = '';
            } else {
                continue;
            }

            if ($old) {
                Storage::disk('public')->delete($old);
            }
        }

        Setting::put($data);

        return back()->with('toast', ['success', '✓ Data berhasil diperbarui']);
    }
}
