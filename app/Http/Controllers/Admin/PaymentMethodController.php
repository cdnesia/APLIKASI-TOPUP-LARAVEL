<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Services\Duitku\DuitkuClient;
use App\Services\Duitku\DuitkuException;
use App\Services\Duitku\PaymentMethodStatus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    /** Folder logo hasil unduhan Duitku (boleh ditimpa saat sinkron ulang) */
    protected const DUITKU_LOGO_DIR = 'payment-methods/duitku/';

    public function index(Request $request, DuitkuClient $duitku, PaymentMethodStatus $status): View
    {
        $methods = PaymentMethod::query()->orderBy('sort_order')->orderBy('name')->get();
        $gateway = [];
        $gatewayError = null;

        if ($duitku->isConfigured()) {
            try {
                $gateway = $status->methods($request->boolean('refresh'));
            } catch (DuitkuException $e) {
                $gatewayError = $e->getMessage();
            }
        }

        // Metode otomatis yang tersimpan tapi tidak lagi tersedia di proyek Duitku tetap ditampilkan
        $listed = collect($gateway)->pluck('paymentMethod');
        $orphans = $methods->whereNotNull('gateway_code')->reject(fn ($m) => $listed->contains($m->gateway_code))->values();

        return view('admin.payment-methods.index', [
            'methods' => $methods,
            'gateway' => $gateway,
            'gatewayError' => $gatewayError,
            'gatewayConfigured' => $duitku->isConfigured(),
            // Kode yang sedang online; null bila daftar tidak berhasil dimuat
            'onlineCodes' => $duitku->isConfigured() && ! $gatewayError ? array_column($gateway, 'paymentMethod') : null,
            'orphans' => $orphans,
        ]);
    }

    public function create(): View
    {
        return view('admin.payment-methods.form', ['method' => new PaymentMethod(['is_active' => true, 'type' => 'bank']), 'manualOnly' => true]);
    }

    public function store(Request $request): RedirectResponse
    {
        PaymentMethod::query()->create($this->validated($request));

        return redirect()->route('admin.payment-methods.index')->with('toast', ['success', '✓ Data berhasil ditambahkan']);
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        return view('admin.payment-methods.form', ['method' => $paymentMethod]);
    }

    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->update($this->validated($request, $paymentMethod));

        return redirect()->route('admin.payment-methods.index')->with('toast', ['success', '✓ Data berhasil diperbarui']);
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        if ($paymentMethod->image) {
            Storage::disk('public')->delete($paymentMethod->image);
        }
        // Order lama tetap menyimpan snapshot data pembayaran
        $paymentMethod->delete();

        return redirect()->route('admin.payment-methods.index')->with('toast', ['success', '✓ Data berhasil dihapus']);
    }

    /**
     * Simpan pilihan centang metode Duitku: yang dicentang dibuat/diaktifkan lengkap dengan logo,
     * yang tidak dicentang dinonaktifkan (tidak dihapus agar riwayat order tetap utuh).
     */
    public function activate(Request $request, PaymentMethodStatus $status): RedirectResponse
    {
        try {
            $available = collect($status->methods())->keyBy('paymentMethod');
        } catch (DuitkuException $e) {
            return back()->with('toast', ['error', '✕ Gagal memperbarui data. '.$e->getMessage()]);
        }

        $automatic = PaymentMethod::query()->whereNotNull('gateway_code')->get();
        $data = $request->validate([
            'codes' => ['array'],
            'codes.*' => ['string', Rule::in($available->keys()->merge($automatic->pluck('gateway_code'))->unique()->values()->all())],
        ]);
        $checked = collect($data['codes'] ?? [])->unique();

        $failedLogos = [];
        $sortOrder = (int) PaymentMethod::query()->max('sort_order');

        foreach ($checked as $code) {
            $item = $available->get($code);
            $method = $automatic->firstWhere('gateway_code', $code)
                ?? PaymentMethod::query()->create([
                    'name' => PaymentMethod::GATEWAY_CODES[$code] ?? Str::title($item['paymentName'] ?? $code),
                    'type' => PaymentMethod::typeForGateway($code),
                    'gateway_code' => $code,
                    'fee' => 0,
                    'sort_order' => ++$sortOrder,
                    'is_active' => true,
                ]);
            $method->update(['is_active' => true]);

            // Logo resmi hanya diunduh bila belum ada; logo unggahan admin tidak disentuh
            if (! $method->image && filled($item['paymentImage'] ?? null)) {
                $path = $this->downloadLogo($item['paymentImage'], "{$method->id}-{$code}");
                $path ? $method->update(['image' => $path]) : $failedLogos[] = $method->name;
            }
        }

        PaymentMethod::query()->whereNotNull('gateway_code')->whereNotIn('gateway_code', $checked->all())->update(['is_active' => false]);

        if ($failedLogos) {
            return back()->with('toast', ['warning', '⚠ Data tersimpan, tetapi logo '.implode(', ', $failedLogos).' gagal diunduh. Logo bawaan dipakai sementara.']);
        }

        return back()->with('toast', ['success', '✓ Data berhasil diperbarui — '.$checked->count().' metode otomatis aktif']);
    }

    /** Simpan gambar dari Duitku: PNG/JPG/WEBP/SVG maks. 1 MB; SVG harus lolos pemeriksaan keamanan */
    protected function downloadLogo(string $url, string $name): ?string
    {
        $extensions = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/svg+xml' => 'svg'];

        try {
            $response = Http::timeout(15)->get($url);
        } catch (ConnectionException) {
            return null;
        }

        $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        $body = $response->body();
        if ($response->failed() || ! isset($extensions[$mime]) || $body === '' || strlen($body) > 1024 * 1024) {
            return null;
        }
        if ($mime === 'image/svg+xml' && ! $this->isSafeSvg($body)) {
            return null;
        }

        $path = self::DUITKU_LOGO_DIR."{$name}.{$extensions[$mime]}";
        Storage::disk('public')->put($path, $body);

        return $path;
    }

    /**
     * SVG disajikan dari domain sendiri, jadi tolak apa pun yang bisa menjalankan kode
     * bila file dibuka langsung: script, atribut event, javascript:, foreignObject, entity, tautan eksternal.
     */
    protected function isSafeSvg(string $svg): bool
    {
        return str_contains($svg, '<svg')
            && ! preg_match('/<\s*script|<\s*foreignObject|<!ENTITY|\son[a-z]+\s*=|javascript:|data:text|href\s*+=\s*+["\']?+\s*+(?!#)/i', $svg);
    }

    protected function validated(Request $request, ?PaymentMethod $method = null): array
    {
        // Metode otomatis (Duitku) tidak butuh rekening maupun gambar QRIS statis
        $manual = blank($request->input('gateway_code'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(PaymentMethod::TYPES))],
            'gateway_code' => ['nullable', Rule::in(array_filter([...array_keys(PaymentMethod::GATEWAY_CODES), $method?->gateway_code]))],
            'account_number' => [Rule::requiredIf($manual && $request->input('type') !== 'qris'), 'nullable', 'string', 'max:50'],
            'account_name' => [Rule::requiredIf($manual && $request->input('type') !== 'qris'), 'nullable', 'string', 'max:100'],
            'image' => [Rule::requiredIf($manual && $request->input('type') === 'qris' && ! $method?->image), 'nullable', 'image', 'max:2048'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'fee' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], [
            'image.required' => 'Gambar QRIS wajib diunggah untuk metode QRIS.',
        ]);

        if ($request->hasFile('image')) {
            if ($method?->image) {
                Storage::disk('public')->delete($method->image);
            }
            $data['image'] = $request->file('image')->store('payment-methods', 'public');
        } else {
            unset($data['image']);
        }

        return $data + [
            'fee' => $data['fee'] ?? 0,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
