<?php

namespace App\Http\Controllers;

use App\Models\ApiLog;
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
        $data = $request->input('data', []);
        $log = ['provider' => 'digiflazz', 'direction' => 'in', 'endpoint' => '/webhook/digiflazz',
            'invoice' => isset($data['ref_id']) ? explode('-', $data['ref_id'])[0] : null, 'request' => $request->all(), 'ip' => $request->ip()];

        if (! $digiflazz->verifyWebhook($request->getContent(), $request->header('X-Hub-Signature'))) {
            ApiLog::record($log + ['success' => false, 'http_status' => 403, 'message' => 'Webhook ditolak: signature tidak valid']);
            Log::warning('Webhook Digiflazz ditolak: signature tidak valid', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Signature tidak valid'], 403);
        }

        $order = Order::query()->where('ref_id', $data['ref_id'] ?? null)->first();

        // ref_id tak dikenal (mis. transaksi dari panel Digiflazz) cukup diabaikan
        if ($order) {
            $orders->applyProviderResult($order, $data);
        }

        ApiLog::record($log + [
            'success' => true,
            'http_status' => 200,
            'message' => $order ? trim(($data['status'] ?? '').' '.($data['message'] ?? '')) : 'ref_id tidak dikenal, diabaikan',
        ]);

        return response()->json(['message' => 'OK']);
    }
}
