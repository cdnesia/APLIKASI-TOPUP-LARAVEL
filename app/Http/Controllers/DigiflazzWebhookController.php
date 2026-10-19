<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Digiflazz\DigiflazzClient;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DigiflazzWebhookController extends Controller
{
    public function __invoke(Request $request, DigiflazzClient $digiflazz, OrderService $orders): JsonResponse
    {
        if (! $digiflazz->verifyWebhook($request->getContent(), $request->header('X-Hub-Signature'))) {
            Log::warning('Webhook Digiflazz ditolak: signature tidak valid', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Signature tidak valid'], 403);
        }

        $data = $request->input('data', []);
        $order = Order::query()->where('ref_id', $data['ref_id'] ?? null)->first();

        // ref_id tak dikenal (mis. transaksi dari panel Digiflazz) cukup diabaikan
        if ($order) {
            $orders->applyProviderResult($order, $data);
        }

        return response()->json(['message' => 'OK']);
    }
}
