<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Brand;
use App\Models\FlashSale;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Digiflazz\DigiflazzClient;
use App\Services\Digiflazz\DigiflazzException;
use App\Services\Duitku\DuitkuClient;
use App\Services\Duitku\DuitkuException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class OrderService
{
    public function __construct(
        protected DigiflazzClient $digiflazz,
        protected DuitkuClient $duitku,
    ) {}

    public function create(Brand $brand, Product $product, PaymentMethod $method, string $customerId, ?string $zoneId, string $whatsapp): Order
    {
        // Digiflazz menerima User ID + Zone ID digabung tanpa pemisah (mis. Mobile Legends)
        $customerNo = $customerId.($zoneId ?? '');
        $display = $zoneId ? "{$customerId} ({$zoneId})" : $customerId;

        return DB::transaction(function () use ($brand, $product, $method, $customerNo, $display, $whatsapp) {
            // Kunci baris flash sale agar kuota tidak terjual melebihi batas saat order bersamaan
            $flashSale = FlashSale::query()->running()->where('product_id', $product->id)
                ->orderBy('price')->lockForUpdate()->first();
            $flashSale?->increment('sold');

            $price = $flashSale?->price ?? $product->selling_price;
            $fee = (int) $method->fee;
            $subtotal = $price + $fee;

            return Order::query()->create([
                'invoice' => Order::generateInvoice(),
                'product_id' => $product->id,
                'brand_id' => $brand->id,
                'payment_method_id' => $method->id,
                'flash_sale_id' => $flashSale?->id,
                'brand_name' => $brand->name,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'customer_no' => $customerNo,
                'customer_display' => $display,
                'whatsapp' => $whatsapp,
                'payment_snapshot' => $method->snapshot(),
                'base_price' => $product->base_price,
                'selling_price' => $price,
                'fee' => $fee,
                // Payment gateway sudah mencocokkan pembayaran sendiri, kode unik tidak diperlukan
                'unique_code' => $code = $method->isAutomatic() ? 0 : $this->uniqueCode($subtotal),
                'total' => $subtotal + $code,
                'payment_status' => PaymentStatus::Unpaid,
                'status' => OrderStatus::Waiting,
                'expires_at' => now()->addHours((int) Setting::get('order_expiry_hours')),
            ]);
        });
    }

    /**
     * Buat tagihan Duitku untuk order otomatis.
     * Bila gagal, order dibatalkan agar kuota flash sale kembali, lalu exception dilempar ulang.
     *
     * @throws DuitkuException
     */
    public function requestPayment(Order $order): Order
    {
        try {
            $invoice = $this->duitku->createInvoice([
                'method' => $order->payment_snapshot['gateway_code'],
                'order_id' => $order->invoice,
                'amount' => $order->total,
                'product' => "{$order->brand_name} {$order->product_name}",
                'email' => Setting::get('contact_email') ?: 'noreply@'.parse_url(config('app.url'), PHP_URL_HOST),
                'phone' => $order->whatsapp,
                'name' => Setting::get('site_name'),
                'callback_url' => route('webhook.duitku'),
                'return_url' => route('orders.show', $order),
                'expiry_minutes' => max(1, (int) now()->diffInMinutes($order->expires_at)),
            ]);
        } catch (DuitkuException $e) {
            Log::error('Duitku gagal membuat tagihan', ['invoice' => $order->invoice, 'error' => $e->getMessage()]);
            $order->update([
                'payment_status' => PaymentStatus::Expired,
                'status' => OrderStatus::Cancelled,
                'admin_note' => 'Tagihan Duitku gagal dibuat: '.$e->getMessage(),
            ]);
            $this->releaseFlashSale($order);

            throw $e;
        }

        $order->update([
            'gateway_reference' => $invoice['reference'] ?? null,
            'pay_code' => ($invoice['vaNumber'] ?? '') ?: null,
            'qr_string' => ($invoice['qrString'] ?? '') ?: null,
            'checkout_url' => $invoice['paymentUrl'] ?? null,
        ]);

        return $order;
    }

    /**
     * Tangani callback Duitku yang signature-nya sudah diverifikasi.
     * Status dicek ulang ke API Duitku agar tidak bergantung pada isi callback saja.
     */
    public function handleGatewayCallback(Order $order, array $payload): void
    {
        if (($payload['resultCode'] ?? null) !== '00') {
            Log::info('Callback Duitku: pembayaran tidak berhasil', ['invoice' => $order->invoice, 'resultCode' => $payload['resultCode'] ?? null]);

            return;
        }

        $this->syncGatewayPayment($order);
    }

    /** Cek status pembayaran ke Duitku dan tandai lunas bila sudah dibayar penuh */
    public function syncGatewayPayment(Order $order): Order
    {
        if (! $order->isAutomaticPayment() || $order->payment_status === PaymentStatus::Paid) {
            return $order;
        }

        $result = $this->duitku->transactionStatus($order->invoice);

        if (($result['statusCode'] ?? null) !== '00') {
            return $order;
        }

        if ((int) ($result['amount'] ?? 0) < $order->total) {
            Log::warning('Duitku: nominal dibayar kurang dari total order', ['invoice' => $order->invoice, 'amount' => $result['amount'] ?? null]);

            return $order;
        }

        $order->update(['gateway_reference' => $result['reference'] ?? $order->gateway_reference]);

        return $this->markAsPaid($order, fromGateway: true);
    }

    public function uploadProof(Order $order, UploadedFile $file): void
    {
        if ($order->payment_proof) {
            Storage::disk('local')->delete($order->payment_proof);
        }

        $order->update([
            // Disk privat: bukti transfer hanya bisa dilihat admin
            'payment_proof' => $file->store('payment-proofs', 'local'),
            'payment_status' => PaymentStatus::WaitingConfirmation,
        ]);
    }

    /**
     * Tandai lunas lalu kirim ke Digiflazz.
     * Titik masuk tunggal: konfirmasi admin maupun callback payment gateway.
     * Dari gateway, order yang sudah kedaluwarsa tetap diproses karena uang pembeli sudah masuk.
     */
    public function markAsPaid(Order $order, bool $fromGateway = false): Order
    {
        $updated = DB::transaction(function () use ($order, $fromGateway) {
            $locked = Order::query()->lockForUpdate()->find($order->id);
            $payable = $locked->canBeConfirmed() || ($fromGateway && $locked->payment_status === PaymentStatus::Expired);

            if (! $payable) {
                return false;
            }

            $locked->update([
                'payment_status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ]);

            return true;
        });

        $order->refresh();

        return $updated ? $this->process($order) : $order;
    }

    public function reject(Order $order, ?string $note): void
    {
        $order->update([
            'payment_status' => PaymentStatus::Rejected,
            'status' => OrderStatus::Cancelled,
            'admin_note' => $note,
        ]);
        $this->releaseFlashSale($order);
    }

    /** Kirim (atau kirim ulang dengan ref_id baru) transaksi ke Digiflazz */
    public function process(Order $order): Order
    {
        if ($order->payment_status !== PaymentStatus::Paid || in_array($order->status, [OrderStatus::Success, OrderStatus::Processing])) {
            return $order;
        }

        $attempt = $order->attempts + 1;
        // ref_id disimpan SEBELUM request, agar bisa dicek ulang walau koneksi terputus
        $order->update([
            'ref_id' => $attempt === 1 ? $order->invoice : "{$order->invoice}-{$attempt}",
            'attempts' => $attempt,
            'status' => OrderStatus::Processing,
            'processed_at' => now(),
            'sn' => null,
            'provider_message' => null,
        ]);

        try {
            $this->applyProviderResult($order, $this->digiflazz->transaction($order->sku, $order->customer_no, $order->ref_id));
        } catch (DigiflazzException $e) {
            Log::error('Digiflazz transaksi gagal', ['invoice' => $order->invoice, 'error' => $e->getMessage()]);
            $order->update(['provider_message' => $e->getMessage().' — silakan klik Cek Status.']);
        }

        return $order->refresh();
    }

    /** Cek status ke Digiflazz dengan mengirim ulang ref_id yang sama */
    public function checkStatus(Order $order): Order
    {
        if (! $order->ref_id) {
            return $order;
        }

        $this->applyProviderResult($order, $this->digiflazz->transaction($order->sku, $order->customer_no, $order->ref_id));

        return $order->refresh();
    }

    /** Terapkan hasil dari respons transaksi atau webhook Digiflazz */
    public function applyProviderResult(Order $order, array $data): void
    {
        // Order yang sudah final tidak diubah lagi (idempotent terhadap webhook ganda)
        if ($order->status !== OrderStatus::Processing) {
            return;
        }

        $status = OrderStatus::fromProvider($data['status'] ?? null);

        $order->update([
            'status' => $status,
            'sn' => filled($data['sn'] ?? null) ? $data['sn'] : $order->sn,
            'provider_rc' => $data['rc'] ?? $order->provider_rc,
            'provider_message' => $data['message'] ?? $order->provider_message,
            'provider_price' => isset($data['price']) ? (int) $data['price'] : $order->provider_price,
            'completed_at' => $status === OrderStatus::Processing ? null : now(),
        ]);
    }

    public function markRefunded(Order $order, ?string $note): void
    {
        $order->update([
            'payment_status' => PaymentStatus::Refunded,
            'status' => OrderStatus::Cancelled,
            'admin_note' => $note,
        ]);
        $this->releaseFlashSale($order);
    }

    /** Kedaluwarsakan order yang belum dibayar & belum upload bukti */
    public function expireUnpaid(): int
    {
        $orders = Order::query()
            ->where('payment_status', PaymentStatus::Unpaid)
            ->where('expires_at', '<', now())
            ->get();

        foreach ($orders as $order) {
            $order->update([
                'payment_status' => PaymentStatus::Expired,
                'status' => OrderStatus::Cancelled,
            ]);
            $this->releaseFlashSale($order);
        }

        return $orders->count();
    }

    /** Kembalikan kuota flash sale dari order yang batal */
    protected function releaseFlashSale(Order $order): void
    {
        if ($order->flash_sale_id) {
            FlashSale::query()->whereKey($order->flash_sale_id)->where('sold', '>', 0)->decrement('sold');
        }
    }

    /** Kode unik 3 digit agar transfer mudah dicocokkan, dihindari bentrok dengan order aktif */
    protected function uniqueCode(int $subtotal): int
    {
        $used = Order::query()
            ->whereIn('payment_status', [PaymentStatus::Unpaid, PaymentStatus::WaitingConfirmation])
            ->whereBetween('total', [$subtotal + 1, $subtotal + 999])
            ->pluck('unique_code')
            ->all();

        $available = array_diff(range(1, 999), $used);

        return $available ? $available[array_rand($available)] : random_int(1, 999);
    }
}
