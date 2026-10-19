<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\Order;
use App\Services\Digiflazz\DigiflazzException;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orders) {}

    public function index(Request $request): View
    {
        $orders = Order::query()
            ->when($request->query('q'), fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('invoice', 'like', "%{$search}%")
                ->orWhere('customer_no', 'like', "%{$search}%")
                ->orWhere('whatsapp', 'like', "%{$search}%")))
            ->when($request->query('payment_status'), fn ($q, $status) => $q->where('payment_status', $status))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'paymentStatuses' => PaymentStatus::cases(),
            'statuses' => OrderStatus::cases(),
        ]);
    }

    public function show(Order $order): View
    {
        return view('admin.orders.show', [
            'order' => $order,
            'apiLogs' => ApiLog::query()->where('invoice', $order->invoice)->latest('id')->limit(50)->get(),
        ]);
    }

    public function proof(Order $order): StreamedResponse
    {
        abort_unless($order->payment_proof && Storage::disk('local')->exists($order->payment_proof), 404);

        return Storage::disk('local')->response($order->payment_proof);
    }

    public function confirm(Order $order): RedirectResponse
    {
        if (! $order->canBeConfirmed()) {
            return back()->with('toast', ['error', '✕ Pesanan ini tidak dapat dikonfirmasi.']);
        }

        $order = $this->orders->markAsPaid($order);

        return back()->with('toast', match ($order->status) {
            OrderStatus::Success => ['success', '✓ Pembayaran dikonfirmasi & transaksi sukses.'],
            OrderStatus::Failed => ['error', '✕ Pembayaran dikonfirmasi, tetapi transaksi Digiflazz gagal: '.$order->provider_message],
            default => ['info', 'ℹ Pembayaran dikonfirmasi. Transaksi sedang diproses Digiflazz.'],
        });
    }

    public function reject(Request $request, Order $order): RedirectResponse
    {
        if (! $order->canBeConfirmed()) {
            return back()->with('toast', ['error', '✕ Pesanan ini tidak dapat ditolak.']);
        }

        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:500']]);
        $this->orders->reject($order, $data['admin_note'] ?? null);

        return back()->with('toast', ['success', '✓ Pembayaran ditolak dan pesanan dibatalkan.']);
    }

    public function checkStatus(Order $order): RedirectResponse
    {
        try {
            $order = $this->orders->checkStatus($order);
        } catch (DigiflazzException $e) {
            return back()->with('toast', ['error', '✕ '.$e->getMessage()]);
        }

        return back()->with('toast', ['info', "ℹ Status terbaru: {$order->status->label()}"]);
    }

    public function retry(Order $order): RedirectResponse
    {
        if ($order->status !== OrderStatus::Failed || $order->payment_status !== PaymentStatus::Paid) {
            return back()->with('toast', ['error', '✕ Hanya transaksi gagal yang sudah lunas yang dapat diulang.']);
        }

        $order = $this->orders->process($order);

        return back()->with('toast', $order->status === OrderStatus::Failed
            ? ['error', '✕ Transaksi masih gagal: '.$order->provider_message]
            : ['success', '✓ Transaksi dikirim ulang ke Digiflazz.']);
    }

    public function refund(Request $request, Order $order): RedirectResponse
    {
        if ($order->status !== OrderStatus::Failed || $order->payment_status !== PaymentStatus::Paid) {
            return back()->with('toast', ['error', '✕ Hanya transaksi gagal yang sudah lunas yang dapat di-refund.']);
        }

        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:500']]);
        $this->orders->markRefunded($order, $data['admin_note'] ?? null);

        return back()->with('toast', ['success', '✓ Pesanan ditandai sudah di-refund.']);
    }
}
