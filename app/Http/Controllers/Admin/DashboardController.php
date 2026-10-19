<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Services\Digiflazz\DigiflazzClient;
use App\Services\Digiflazz\DigiflazzException;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(DigiflazzClient $digiflazz): View
    {
        $balance = null;
        $balanceError = null;

        try {
            $balance = $digiflazz->balance();
        } catch (DigiflazzException $e) {
            $balanceError = $e->getMessage();
        }

        $today = Order::query()->whereDate('created_at', today());
        $successToday = (clone $today)->where('status', OrderStatus::Success)->get(['selling_price', 'base_price', 'provider_price']);

        return view('admin.dashboard', [
            'balance' => $balance,
            'balanceError' => $balanceError,
            'isTesting' => $digiflazz->isTesting(),
            'stats' => [
                'orders_today' => (clone $today)->count(),
                'revenue_today' => $successToday->sum('selling_price'),
                'profit_today' => $successToday->sum(fn (Order $o) => $o->profit()),
                'need_confirmation' => Order::query()->where('payment_status', PaymentStatus::WaitingConfirmation)->count(),
                'processing' => Order::query()->where('status', OrderStatus::Processing)->count(),
                'products' => Product::query()->count(),
            ],
            'hasPaymentMethod' => PaymentMethod::query()->where('is_active', true)->exists(),
            'latestOrders' => Order::query()->latest()->limit(8)->get(),
        ]);
    }
}
