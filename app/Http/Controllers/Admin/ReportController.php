<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ]);

        $from = Carbon::parse($request->query('dari', now()->subDays(29)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('sampai', now()->toDateString()))->endOfDay();

        // Hanya transaksi sukses yang dihitung sebagai omzet & laba
        $orders = Order::query()
            ->where('status', OrderStatus::Success)
            ->whereBetween('completed_at', [$from, $to])
            ->get(['id', 'brand_name', 'product_name', 'selling_price', 'base_price', 'provider_price', 'fee', 'unique_code', 'completed_at']);

        $daily = collect();
        for ($day = $from->copy(); $day <= $to; $day->addDay()) {
            $rows = $orders->filter(fn (Order $o) => $o->completed_at->isSameDay($day));
            $daily->push([
                'date' => $day->copy(),
                'count' => $rows->count(),
                'revenue' => $rows->sum('selling_price'),
                'profit' => $rows->sum(fn (Order $o) => $o->profit()),
            ]);
        }

        $topProducts = $orders->groupBy(fn (Order $o) => "{$o->brand_name} — {$o->product_name}")
            ->map(fn ($rows, $name) => [
                'name' => $name,
                'count' => $rows->count(),
                'revenue' => $rows->sum('selling_price'),
                'profit' => $rows->sum(fn (Order $o) => $o->profit()),
            ])
            ->sortByDesc('profit')
            ->take(10)
            ->values();

        return view('admin.reports', [
            'from' => $from,
            'to' => $to,
            'summary' => [
                'count' => $orders->count(),
                'revenue' => $orders->sum('selling_price'),
                'profit' => $orders->sum(fn (Order $o) => $o->profit()),
                'extra' => $orders->sum(fn (Order $o) => $o->fee + $o->unique_code),
                'failed' => Order::query()->where('status', OrderStatus::Failed)->whereBetween('created_at', [$from, $to])->count(),
            ],
            'daily' => $daily,
            'topProducts' => $topProducts,
        ]);
    }
}
