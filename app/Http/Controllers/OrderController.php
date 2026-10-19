<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Brand;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Services\Duitku\DuitkuException;
use App\Services\Duitku\PaymentMethodStatus;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders, protected PaymentMethodStatus $paymentStatus) {}

    public function store(Request $request, Brand $brand): RedirectResponse
    {
        abort_unless($brand->is_active, 404);

        $request->merge(['whatsapp' => $this->normalizePhone((string) $request->input('whatsapp'))]);

        $data = $request->validate([
            'customer_id' => ['required', 'string', 'max:40', 'regex:/^[0-9A-Za-z._\-]+$/'],
            'zone_id' => [Rule::requiredIf(filled($brand->zone_label)), 'nullable', 'string', 'max:20', 'regex:/^[0-9A-Za-z]+$/'],
            'product_id' => ['required', Rule::exists('products', 'id')->where('brand_id', $brand->id)],
            'payment_method_id' => ['required', Rule::exists('payment_methods', 'id')->where('is_active', true)],
            'whatsapp' => ['required', 'regex:/^628[0-9]{7,12}$/'],
        ], [
            'customer_id.required' => "{$brand->field_label} wajib diisi.",
            'customer_id.regex' => "{$brand->field_label} hanya boleh berisi huruf dan angka.",
            'zone_id.required' => "{$brand->zone_label} wajib diisi.",
            'product_id.required' => 'Silakan pilih nominal terlebih dahulu.',
            'payment_method_id.required' => 'Silakan pilih metode pembayaran.',
            'whatsapp.required' => 'Nomor WhatsApp wajib diisi.',
            'whatsapp.regex' => 'Format nomor WhatsApp tidak valid. Contoh: 081234567890',
        ]);

        $product = Product::query()->findOrFail($data['product_id']);

        if (! $product->isAvailable()) {
            return back()->withInput()->with('toast', ['warning', '⚠ Produk sedang gangguan. Silakan pilih nominal lain.']);
        }

        $method = PaymentMethod::query()->findOrFail($data['payment_method_id']);

        if ($product->currentPrice() + $method->fee < $method->minAmount()) {
            return back()->withInput()
                ->withErrors(['payment_method_id' => "{$method->name} hanya untuk total minimal ".rupiah($method->minAmount()).'. Pilih metode lain, mis. QRIS.'])
                ->with('toast', ['warning', '⚠ Periksa kembali isian form Anda.']);
        }

        // Cek status setelah validasi lokal agar tidak memanggil Duitku tanpa perlu
        if ($this->paymentStatus->isOnline($method) === false) {
            return back()->withInput()
                ->withErrors(['payment_method_id' => "{$method->name} sedang offline. Silakan pilih metode pembayaran lain."])
                ->with('toast', ['warning', '⚠ Periksa kembali isian form Anda.']);
        }

        $order = $this->orders->create(
            $brand,
            $product,
            $method,
            $data['customer_id'],
            filled($brand->zone_label) ? $data['zone_id'] : null,
            $data['whatsapp'],
        );

        if ($method->isAutomatic()) {
            try {
                $this->orders->requestPayment($order);
            } catch (DuitkuException) {
                return back()->withInput()
                    ->with('toast', ['error', '✕ Gagal membuat tagihan pembayaran. Coba lagi atau pilih metode lain.']);
            }
        }

        return redirect()->route('orders.show', $order)
            ->with('toast', ['success', '✓ Pesanan berhasil dibuat. Silakan lakukan pembayaran.']);
    }

    public function show(Order $order): View
    {
        if ($order->isExpired()) {
            $this->orders->expireUnpaid();
            $order->refresh();
        }

        return view('order', ['order' => $order]);
    }

    /** Tombol "Cek Status Pembayaran" — cadangan bila callback Duitku belum masuk */
    public function checkPayment(Order $order): RedirectResponse
    {
        if (! $order->isAutomaticPayment() || ! $order->canBeConfirmed()) {
            return back();
        }

        try {
            $order = $this->orders->syncGatewayPayment($order);
        } catch (DuitkuException) {
            return back()->with('toast', ['error', '✕ Gagal memuat data. Periksa koneksi Anda.']);
        }

        return back()->with('toast', $order->payment_status === PaymentStatus::Paid
            ? ['success', '✓ Pembayaran diterima. Pesanan sedang diproses.']
            : ['info', 'ℹ Pembayaran belum diterima. Selesaikan pembayaran lalu cek kembali.']);
    }

    public function uploadProof(Request $request, Order $order): RedirectResponse
    {
        if (! $order->canUploadProof()) {
            return back()->with('toast', ['error', '✕ Bukti pembayaran tidak dapat diunggah untuk pesanan ini.']);
        }

        $request->validate([
            'proof' => ['required', 'image', 'max:4096'],
        ], [
            'proof.required' => 'Silakan pilih foto bukti transfer.',
            'proof.image' => 'File harus berupa gambar (JPG/PNG).',
            'proof.max' => 'Ukuran gambar maksimal 4 MB.',
        ]);

        $this->orders->uploadProof($order, $request->file('proof'));

        return back()->with('toast', ['success', '✓ Bukti pembayaran terkirim. Pesanan segera diproses admin.']);
    }

    public function track(Request $request): View|RedirectResponse
    {
        $invoice = strtoupper(trim((string) $request->query('invoice')));

        if ($invoice === '') {
            return view('track');
        }

        $order = Order::query()->where('invoice', $invoice)->first();

        if (! $order) {
            return redirect()->route('track')->withInput()
                ->with('toast', ['error', '✕ Nomor invoice tidak ditemukan. Periksa kembali.']);
        }

        return redirect()->route('orders.show', $order);
    }

    /** 0812xxx / +62812xxx / 812xxx -> 62812xxx */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        return match (true) {
            str_starts_with($phone, '0') => '62'.substr($phone, 1),
            str_starts_with($phone, '8') => '62'.$phone,
            default => $phone,
        };
    }
}
