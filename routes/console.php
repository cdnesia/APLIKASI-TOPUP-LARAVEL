<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Services\Digiflazz\DigiflazzException;
use App\Services\Duitku\DuitkuException;
use App\Services\OrderService;
use App\Services\ProductSyncService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

Artisan::command('admin:user {email?} {--name=Administrator} {--password=}', function () {
    // Aman di produksi: membuat akun admin baru atau mereset password akun yang sudah ada
    $email = $this->argument('email') ?? text('Email admin', default: (string) config('app.admin.email'), required: true);
    $plain = $this->option('password') ?? password('Password baru (min. 8 karakter)', required: true);

    $validator = Validator::make(['email' => $email, 'password' => $plain], [
        'email' => ['required', 'email'],
        'password' => ['required', 'string', 'min:8'],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $user = User::query()->firstOrNew(['email' => $email]);
    $isNew = ! $user->exists;
    $user->fill(['name' => $user->name ?? $this->option('name'), 'password' => $plain])->save();

    $this->info($isNew ? "Akun admin {$email} berhasil dibuat." : "Password akun {$email} berhasil diperbarui.");
})->purpose('Buat akun admin atau reset password admin');

Artisan::command('digiflazz:sync', function (ProductSyncService $sync) {
    try {
        $stats = $sync->sync();
    } catch (DigiflazzException $e) {
        $this->error('Sinkronisasi gagal: '.$e->getMessage());

        return 1;
    }

    $this->info("Selesai: {$stats['created']} baru, {$stats['updated']} diperbarui, {$stats['deactivated']} dinonaktifkan.");
})->purpose('Sinkronkan produk & harga dari Digiflazz');

Artisan::command('orders:expire', function (OrderService $orders) {
    $this->info($orders->expireUnpaid().' pesanan dikedaluwarsakan.');
})->purpose('Batalkan pesanan yang melewati batas waktu pembayaran');

Artisan::command('orders:check-pending', function (OrderService $orders) {
    // Cadangan bila webhook Digiflazz tidak sampai
    Order::query()
        ->where('status', OrderStatus::Processing)
        ->where('processed_at', '<', now()->subMinutes(5))
        ->each(function (Order $order) use ($orders) {
            try {
                $orders->checkStatus($order);
            } catch (DigiflazzException $e) {
                $this->warn("{$order->invoice}: {$e->getMessage()}");
            }
        });
})->purpose('Cek ulang status transaksi yang masih diproses');

Artisan::command('orders:check-payments', function (OrderService $orders) {
    // Cadangan bila callback Duitku tidak sampai (mis. server sempat down)
    Order::query()
        ->where('payment_status', PaymentStatus::Unpaid)
        ->whereNotNull('gateway_reference')
        ->where('created_at', '<', now()->subMinutes(2))
        ->where('expires_at', '>', now()->subHour())
        ->each(function (Order $order) use ($orders) {
            try {
                if ($orders->syncGatewayPayment($order)->payment_status === PaymentStatus::Paid) {
                    $this->info("{$order->invoice}: lunas");
                }
            } catch (DuitkuException $e) {
                $this->warn("{$order->invoice}: {$e->getMessage()}");
            }
        });
})->purpose('Cek ulang status pembayaran Duitku yang belum lunas');

Schedule::command('digiflazz:sync')->hourly()->withoutOverlapping();
Schedule::command('orders:check-payments')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('orders:expire')->everyFiveMinutes();
Schedule::command('orders:check-pending')->everyTenMinutes()->withoutOverlapping();
