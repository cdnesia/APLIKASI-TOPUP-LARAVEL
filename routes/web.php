<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\DigiflazzWebhookController;
use App\Http\Controllers\DuitkuCallbackController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

// Halaman publik
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/cek-transaksi', [OrderController::class, 'track'])->name('track');
Route::get('/daftar-harga', [PageController::class, 'priceList'])->name('price-list');
Route::get('/cara-order', [PageController::class, 'guide'])->name('guide');
Route::get('/kontak', [PageController::class, 'contact'])->name('contact');
Route::get('/syarat-ketentuan', [PageController::class, 'terms'])->name('terms');
Route::get('/kebijakan-privasi', [PageController::class, 'privacy'])->name('privacy');
Route::get('/topup/{brand}', [BrandController::class, 'show'])->name('brands.show');
Route::post('/topup/{brand}', [OrderController::class, 'store'])->middleware('throttle:10,1')->name('orders.store');
Route::get('/invoice/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::post('/invoice/{order}/cek-pembayaran', [OrderController::class, 'checkPayment'])->middleware('throttle:6,1')->name('orders.check-payment');
Route::post('/invoice/{order}/bukti', [OrderController::class, 'uploadProof'])->middleware('throttle:10,1')->name('orders.proof');

// Webhook Digiflazz & callback Duitku (dikecualikan dari CSRF di bootstrap/app.php)
Route::post('/webhook/digiflazz', DigiflazzWebhookController::class)->name('webhook.digiflazz');
Route::post('/webhook/duitku', DuitkuCallbackController::class)->name('webhook.duitku');

// Autentikasi admin
Route::middleware('guest')->group(function () {
    Route::get('/admin/login', [LoginController::class, 'create'])->name('login');
    Route::post('/admin/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('/transaksi', [Admin\OrderController::class, 'index'])->name('orders.index');
    Route::get('/transaksi/{order}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::get('/transaksi/{order}/bukti', [Admin\OrderController::class, 'proof'])->name('orders.proof');
    Route::post('/transaksi/{order}/konfirmasi', [Admin\OrderController::class, 'confirm'])->name('orders.confirm');
    Route::post('/transaksi/{order}/tolak', [Admin\OrderController::class, 'reject'])->name('orders.reject');
    Route::post('/transaksi/{order}/cek-status', [Admin\OrderController::class, 'checkStatus'])->name('orders.check');
    Route::post('/transaksi/{order}/ulangi', [Admin\OrderController::class, 'retry'])->name('orders.retry');
    Route::post('/transaksi/{order}/refund', [Admin\OrderController::class, 'refund'])->name('orders.refund');

    Route::get('/produk', [Admin\ProductController::class, 'index'])->name('products.index');
    Route::put('/produk/{product}', [Admin\ProductController::class, 'update'])->name('products.update');
    Route::post('/produk/{product}/gambar', [Admin\ProductController::class, 'image'])->name('products.image');
    Route::delete('/produk/{product}/gambar', [Admin\ProductController::class, 'destroyImage'])->name('products.image.destroy');
    Route::post('/brand/{brand:id}/gambar-nominal', [Admin\ProductController::class, 'brandImage'])->name('brands.product-image');
    Route::post('/produk/sinkron', [Admin\ProductController::class, 'sync'])->name('products.sync');
    Route::post('/produk/hitung-ulang', [Admin\ProductController::class, 'reprice'])->name('products.reprice');

    Route::get('/brand', [Admin\BrandController::class, 'index'])->name('brands.index');
    Route::get('/brand/{brand:id}/edit', [Admin\BrandController::class, 'edit'])->name('brands.edit');
    Route::put('/brand/{brand:id}', [Admin\BrandController::class, 'update'])->name('brands.update');

    Route::resource('metode-pembayaran', Admin\PaymentMethodController::class)
        ->except('show')
        ->parameters(['metode-pembayaran' => 'paymentMethod'])
        ->names('payment-methods');

    Route::resource('banner', Admin\BannerController::class)->except('show')
        ->parameters(['banner' => 'banner'])->names('banners');
    Route::resource('flash-sale', Admin\FlashSaleController::class)->except('show')
        ->parameters(['flash-sale' => 'flashSale'])->names('flash-sales');

    Route::get('/laporan', Admin\ReportController::class)->name('reports');
    Route::get('/log-api', [Admin\ApiLogController::class, 'index'])->name('api-logs.index');

    Route::get('/profil', [Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [Admin\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/password', [Admin\ProfileController::class, 'password'])->name('profile.password');

    Route::get('/pengaturan', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('/pengaturan', [Admin\SettingController::class, 'update'])->name('settings.update');
});
