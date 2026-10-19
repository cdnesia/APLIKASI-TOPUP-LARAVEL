<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Duitku\DuitkuClient;
use App\Services\Duitku\DuitkuException;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class DuitkuCallbackController extends Controller
{
    public function __invoke(Request $request, DuitkuClient $duitku, OrderService $orders): Response
    {
        // Duitku mengirim callback sebagai form-urlencoded
        $payload = $request->only(['merchantCode', 'amount', 'merchantOrderId', 'resultCode', 'reference', 'signature']);

        if (! $duitku->verifyCallback($payload)) {
            Log::warning('Callback Duitku ditolak: signature tidak valid', ['ip' => $request->ip(), 'order' => $payload['merchantOrderId'] ?? null]);

            return response('Signature tidak valid', 403);
        }

        $order = Order::query()->where('invoice', $payload['merchantOrderId'])->first();

        if (! $order) {
            return response('Order tidak ditemukan', 404);
        }

        try {
            $orders->handleGatewayCallback($order, $payload);
        } catch (DuitkuException $e) {
            // Balas gagal agar Duitku mengirim ulang callback nanti
            Log::error('Callback Duitku gagal diverifikasi ulang', ['invoice' => $order->invoice, 'error' => $e->getMessage()]);

            return response('Gagal verifikasi status', 500);
        }

        return response('SUCCESS');
    }
}
