<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(): View
    {
        return view('admin.payment-methods.index', [
            'methods' => PaymentMethod::query()->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.payment-methods.form', ['method' => new PaymentMethod(['is_active' => true, 'type' => 'bank'])]);
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

    protected function validated(Request $request, ?PaymentMethod $method = null): array
    {
        // Metode otomatis (Duitku) tidak butuh rekening maupun gambar QRIS statis
        $manual = blank($request->input('gateway_code'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(PaymentMethod::TYPES))],
            'gateway_code' => ['nullable', Rule::in(array_keys(PaymentMethod::GATEWAY_CODES))],
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
