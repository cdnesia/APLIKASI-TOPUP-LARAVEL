<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\ApiLog;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\FlashSale;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TopupFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.digiflazz.username' => 'tester',
            'services.digiflazz.key' => 'secret-key',
            'services.digiflazz.testing' => true,
            'services.digiflazz.webhook_secret' => 'hook-secret',
        ]);
        Setting::put(['margin_percent' => '10', 'margin_flat' => '0', 'price_rounding' => '100']);
    }

    protected function fakePriceList(): void
    {
        Http::fake([
            '*/price-list' => Http::response(['data' => [
                [
                    'product_name' => 'MOBILE LEGENDS 86 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS',
                    'type' => 'Umum', 'price' => 19_250, 'buyer_sku_code' => 'ML86', 'buyer_product_status' => true,
                    'seller_product_status' => true, 'unlimited_stock' => true, 'stock' => 0, 'desc' => '-',
                ],
                [
                    'product_name' => 'Telkomsel 10.000', 'category' => 'Pulsa', 'brand' => 'TELKOMSEL',
                    'type' => 'Umum', 'price' => 10_150, 'buyer_sku_code' => 'S10', 'buyer_product_status' => true,
                    'seller_product_status' => false, 'unlimited_stock' => true, 'stock' => 0, 'desc' => '-',
                ],
            ]]),
        ]);
    }

    protected function makeOrderSetup(): array
    {
        $this->fakePriceList();
        $this->artisan('digiflazz:sync')->assertSuccessful();

        $method = PaymentMethod::query()->create([
            'name' => 'Transfer BCA', 'type' => 'bank', 'account_number' => '1234567890',
            'account_name' => 'ELHAZMI', 'fee' => 0, 'is_active' => true,
        ]);

        return [Brand::query()->where('slug', 'mobile-legends-games')->firstOrFail(), $method];
    }

    public function test_sync_creates_brands_products_and_prices_with_margin(): void
    {
        [$brand] = $this->makeOrderSetup();

        $ml = Product::query()->where('sku', 'ML86')->first();
        $this->assertSame(21_200, $ml->selling_price); // 19.250 + 10% = 21.175 -> dibulatkan 21.200
        $this->assertSame('Zone ID', $brand->zone_label);
        $this->assertFalse(Product::query()->where('sku', 'S10')->first()->provider_active);

        Http::assertSent(fn ($request) => $request['sign'] === md5('tester'.'secret-key'.'pricelist'));

        // Price list besar diringkas di log, sign disamarkan
        $log = ApiLog::query()->where('endpoint', '/price-list')->firstOrFail();
        $this->assertTrue($log->success);
        $this->assertSame('2 produk', $log->message);
        $this->assertSame('***', $log->request['sign']);
    }

    public function test_customer_can_order_and_admin_confirmation_sends_to_digiflazz(): void
    {
        [$brand, $method] = $this->makeOrderSetup();
        $product = Product::query()->where('sku', 'ML86')->first();

        $this->get(route('home'))->assertOk()->assertSee('Mobile Legends');
        $this->get(route('brands.show', $brand))->assertOk()->assertSee('86 Diamond');

        $response = $this->post(route('orders.store', $brand), [
            'customer_id' => '12345678',
            'zone_id' => '1234',
            'product_id' => $product->id,
            'payment_method_id' => $method->id,
            'whatsapp' => '0812-3456-7890',
        ]);

        $order = Order::query()->firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame('123456781234', $order->customer_no);
        $this->assertSame('6281234567890', $order->whatsapp);
        $this->assertSame($product->selling_price + $order->unique_code, $order->total);
        $this->get(route('orders.show', $order))->assertOk()->assertSee('1234567890');

        // Upload bukti transfer
        Storage::fake('local');
        $this->post(route('orders.proof', $order), ['proof' => UploadedFile::fake()->image('bukti.jpg')])->assertRedirect();
        $this->assertSame(PaymentStatus::WaitingConfirmation, $order->refresh()->payment_status);
        Storage::disk('local')->assertExists($order->payment_proof);

        // Admin konfirmasi -> transaksi dikirim ke Digiflazz
        Http::fake(['*/transaction' => Http::response(['data' => [
            'ref_id' => $order->invoice, 'status' => 'Pending', 'rc' => '03', 'message' => 'Transaksi Pending', 'sn' => '', 'price' => 19_250,
        ]])]);

        $admin = User::factory()->create();
        $this->actingAs($admin)->post(route('admin.orders.confirm', $order))->assertRedirect();

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Processing, $order->status);
        $this->assertSame($order->invoice, $order->ref_id);
        Http::assertSent(fn ($request) => $request['customer_no'] === '123456781234'
            && $request['testing'] === true
            && $request['sign'] === md5('tester'.'secret-key'.$order->invoice));

        // Webhook sukses dari Digiflazz
        $payload = json_encode(['data' => ['ref_id' => $order->ref_id, 'status' => 'Sukses', 'rc' => '00', 'sn' => 'SN-ABC-123', 'message' => 'Transaksi Sukses', 'price' => 19_250]]);
        $this->call('POST', route('webhook.digiflazz'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_HUB_SIGNATURE' => 'sha1='.hash_hmac('sha1', $payload, 'hook-secret'),
        ], $payload)->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::Success, $order->status);
        $this->assertSame('SN-ABC-123', $order->sn);
        $this->get(route('orders.show', $order))->assertSee('SN-ABC-123');
    }

    public function test_webhook_with_invalid_signature_is_rejected(): void
    {
        $this->postJson(route('webhook.digiflazz'), ['data' => ['ref_id' => 'X', 'status' => 'Sukses']], ['X-Hub-Signature' => 'sha1=palsu'])
            ->assertForbidden();
    }

    public function test_failed_transaction_can_be_retried_with_new_ref_id(): void
    {
        [$brand, $method] = $this->makeOrderSetup();
        $product = Product::query()->where('sku', 'ML86')->first();
        $this->post(route('orders.store', $brand), [
            'customer_id' => '12345678', 'zone_id' => '1234', 'product_id' => $product->id,
            'payment_method_id' => $method->id, 'whatsapp' => '081234567890',
        ]);
        $order = Order::query()->firstOrFail();

        Http::fake(['*/transaction' => Http::sequence()
            ->push(['data' => ['status' => 'Gagal', 'rc' => '44', 'message' => 'Saldo tidak cukup']])
            ->push(['data' => ['status' => 'Sukses', 'rc' => '00', 'sn' => 'SN-2', 'message' => 'Sukses']])]);

        $this->actingAs(User::factory()->create());
        $this->post(route('admin.orders.confirm', $order));
        $this->assertSame(OrderStatus::Failed, $order->refresh()->status);

        $this->post(route('admin.orders.retry', $order));
        $order->refresh();
        $this->assertSame(OrderStatus::Success, $order->status);
        $this->assertSame("{$order->invoice}-2", $order->ref_id);
    }

    public function test_order_validation_and_unavailable_product(): void
    {
        [$brand, $method] = $this->makeOrderSetup();
        $pulsa = Product::query()->where('sku', 'S10')->first();

        // Produk brand lain / zone kosong / WA salah
        $this->post(route('orders.store', $brand), [
            'customer_id' => '123', 'product_id' => $pulsa->id, 'payment_method_id' => $method->id, 'whatsapp' => '12',
        ])->assertSessionHasErrors(['zone_id', 'product_id', 'whatsapp']);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_unpaid_orders_expire(): void
    {
        [$brand, $method] = $this->makeOrderSetup();
        $this->post(route('orders.store', $brand), [
            'customer_id' => '1', 'zone_id' => '2', 'product_id' => Product::query()->where('sku', 'ML86')->value('id'),
            'payment_method_id' => $method->id, 'whatsapp' => '081234567890',
        ]);

        $this->travel(4)->hours();
        $this->artisan('orders:expire')->assertSuccessful();
        $this->assertSame(PaymentStatus::Expired, Order::query()->first()->payment_status);
    }

    public function test_admin_user_command_creates_and_resets_password(): void
    {
        $this->artisan('admin:user', ['email' => 'owner@elhazmi.com', '--password' => 'rahasia123'])
            ->expectsOutputToContain('berhasil dibuat')->assertSuccessful();
        $this->assertTrue(Hash::check('rahasia123', User::query()->where('email', 'owner@elhazmi.com')->value('password')));

        $this->artisan('admin:user', ['email' => 'owner@elhazmi.com', '--password' => 'gantibaru456'])
            ->expectsOutputToContain('berhasil diperbarui')->assertSuccessful();
        $this->assertTrue(Hash::check('gantibaru456', User::query()->where('email', 'owner@elhazmi.com')->value('password')));

        $this->artisan('admin:user', ['email' => 'owner@elhazmi.com', '--password' => 'pendek'])->assertFailed();
        $this->post(route('login'), ['email' => 'owner@elhazmi.com', 'password' => 'gantibaru456'])->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_can_upload_and_remove_logo_and_favicon(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $base = array_diff_key(Setting::DEFAULTS, array_flip(Setting::IMAGES));

        $this->actingAs($admin)->put(route('admin.settings.update'), $base + [
            'logo' => UploadedFile::fake()->image('logo.png', 400, 80),
            'logo_dark' => UploadedFile::fake()->image('logo-putih.png', 400, 80),
            'favicon' => UploadedFile::fake()->image('ikon.png', 512, 512),
        ])->assertRedirect()->assertSessionHasNoErrors();

        foreach (['logo', 'logo_dark', 'favicon'] as $key) {
            $this->assertStringStartsWith('branding/', Setting::get($key));
            Storage::disk('public')->assertExists(Setting::get($key));
        }

        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString(Setting::imageUrl('logo'), $html);
        $this->assertStringContainsString(Setting::imageUrl('logo_dark'), $html); // footer
        $this->assertStringContainsString('<link rel="icon" href="'.Setting::imageUrl('favicon').'"', $html);

        // SVG ditolak karena bisa berisi skrip
        $this->put(route('admin.settings.update'), $base + [
            'logo' => UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
        ])->assertSessionHasErrors('logo');

        $oldFavicon = Setting::get('favicon');
        $this->put(route('admin.settings.update'), $base + ['remove_favicon' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('', Setting::get('favicon'));
        Storage::disk('public')->assertMissing($oldFavicon);
        $this->get(route('home'))->assertSee('favicon.svg');
    }

    public function test_seo_sitemap_robots_and_meta_tags(): void
    {
        [$brand] = $this->makeOrderSetup();
        Setting::put(['google_site_verification' => 'kodeVerifikasi123']);

        $this->get(route('sitemap'))->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('brands.show', $brand), false)
            ->assertSee(route('price-list'), false);

        $this->get(route('robots'))->assertOk()
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /invoice/')
            ->assertSee('Sitemap: '.route('sitemap'));

        $this->get(route('brands.show', $brand))->assertOk()
            ->assertSee('<title>Top Up Mobile Legends Murah &amp; Instan', false)
            ->assertSee('<link rel="canonical" href="'.route('brands.show', $brand).'">', false)
            ->assertSee('<meta name="google-site-verification" content="kodeVerifikasi123">', false)
            ->assertSee('"@type":"AggregateOffer"', false)
            ->assertSee('index, follow', false);

        // Invoice berisi data pembeli: tidak boleh diindeks
        $order = Order::query()->create([
            'invoice' => 'ELHSEO1', 'brand_name' => 'X', 'product_name' => 'X', 'sku' => 'X', 'customer_no' => '1',
            'customer_display' => '1', 'whatsapp' => '62812', 'base_price' => 1, 'selling_price' => 1, 'total' => 1,
            'payment_status' => PaymentStatus::Unpaid, 'status' => OrderStatus::Waiting, 'expires_at' => now()->addHour(),
        ]);
        $this->get(route('orders.show', $order))->assertSee('noindex, nofollow', false)->assertDontSee('application/ld+json', false);
    }

    public function test_admin_can_edit_seo_meta_for_home_and_brand(): void
    {
        [$brand] = $this->makeOrderSetup();
        $admin = User::factory()->create();
        $base = array_diff_key(Setting::DEFAULTS, array_flip(Setting::IMAGES));

        // Template brand & judul beranda dari Pengaturan
        $this->actingAs($admin)->put(route('admin.settings.update'), array_merge($base, [
            'seo_home_title' => 'Top Up Game Termurah se-Indonesia',
            'seo_home_description' => 'Deskripsi beranda buatan admin.',
            'seo_brand_title' => 'Beli {nama} di {situs}',
            'seo_brand_description' => '{nama} mulai {harga_mulai}.',
        ]))->assertSessionHasNoErrors();

        $this->get(route('home'))
            ->assertSee('<title>Top Up Game Termurah se-Indonesia</title>', false)
            ->assertSee('content="Deskripsi beranda buatan admin."', false);
        $this->get(route('brands.show', $brand))
            ->assertSee('<title>Beli Mobile Legends di elhazmi.com · elhazmi.com</title>', false)
            ->assertSee('content="Mobile Legends mulai Rp21.200."', false);

        // Isian khusus brand menimpa template
        $this->put(route('admin.brands.update', $brand), [
            'name' => $brand->name, 'field_label' => 'User ID', 'is_active' => 1,
            'seo_title' => 'Diamond ML Murah', 'seo_description' => 'Khusus {nama}.',
        ])->assertSessionHasNoErrors();

        $this->get(route('brands.show', $brand))
            ->assertSee('<title>Diamond ML Murah · elhazmi.com</title>', false)
            ->assertSee('content="Khusus Mobile Legends."', false);
        $this->get(route('admin.brands.edit', $brand))->assertOk()->assertSee('Pratinjau di Google');
        $this->get(route('admin.settings.edit'))->assertOk()->assertSee('Template Halaman Game');
    }

    public function test_admin_pages_render_and_require_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();

        $this->makeOrderSetup();
        Http::fake(['*/cek-saldo' => Http::response(['data' => ['deposit' => 500_000]])]);
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Rp500.000');
        $this->get(route('admin.orders.index'))->assertOk()->assertSee('Belum ada transaksi');
        $this->get(route('admin.products.index'))->assertOk()->assertSee('ML86');
        $this->get(route('admin.brands.index'))->assertOk();
        $this->get(route('admin.brands.edit', Brand::query()->first()->id))->assertOk();
        $this->get(route('admin.payment-methods.index'))->assertOk();
        $this->get(route('admin.payment-methods.create'))->assertOk();
        $this->get(route('admin.settings.edit'))->assertOk()->assertSee('webhook/digiflazz');
        $this->get(route('track'))->assertOk();
    }

    public function test_public_pages_render(): void
    {
        $this->makeOrderSetup();

        $this->get(route('home', ['kategori' => 'Games']))->assertOk()->assertSee('Mobile Legends')->assertDontSee('Telkomsel</span>', false);
        $this->get(route('price-list'))->assertOk()->assertSee('86 Diamond')->assertSee('Rp21.200');
        $this->get(route('price-list', ['q' => 'tidak-ada']))->assertOk()->assertSee('Produk tidak ditemukan');
        $this->get(route('guide'))->assertOk()->assertSee('Transfer BCA');
        $this->get(route('contact'))->assertOk();
        $this->get(route('terms'))->assertOk()->assertSee('Pengembalian Dana');
        $this->get(route('privacy'))->assertOk();
    }

    public function test_admin_report_and_profile(): void
    {
        [$brand, $method] = $this->makeOrderSetup();
        $this->post(route('orders.store', $brand), [
            'customer_id' => '1', 'zone_id' => '2', 'product_id' => Product::query()->where('sku', 'ML86')->value('id'),
            'payment_method_id' => $method->id, 'whatsapp' => '081234567890',
        ]);
        Http::fake(['*/transaction' => Http::response(['data' => ['status' => 'Sukses', 'rc' => '00', 'sn' => 'SN', 'price' => 19_250]])]);

        $admin = User::factory()->create(['password' => 'lama12345']);
        $this->actingAs($admin)->post(route('admin.orders.confirm', Order::query()->first()));

        // Laba = 21.200 - 19.250
        $this->get(route('admin.reports'))->assertOk()->assertSee('Rp1.950')->assertSee('Mobile Legends — MOBILE LEGENDS 86 Diamond');

        $this->put(route('admin.profile.password'), ['current_password' => 'salah', 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('admin.profile.password'), ['current_password' => 'lama12345', 'password' => 'baru12345', 'password_confirmation' => 'baru12345'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('baru12345', $admin->refresh()->password));
        $this->get(route('admin.profile.edit'))->assertOk();
    }

    public function test_admin_can_manage_brand_and_product_images(): void
    {
        [$brand] = $this->makeOrderSetup();
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        // Logo + banner brand
        $this->put(route('admin.brands.update', $brand->id), [
            'name' => 'Mobile Legends', 'field_label' => 'User ID', 'is_active' => 1,
            'image' => UploadedFile::fake()->image('logo.png'), 'banner' => UploadedFile::fake()->image('banner.jpg', 1200, 400),
        ])->assertSessionHasNoErrors();
        $brand->refresh();
        Storage::disk('public')->assertExists([$brand->image, $brand->banner]);

        // Nominal tanpa gambar memakai logo brand
        $product = $brand->products()->first();
        $this->assertSame($brand->imageUrl(), $product->imageUrl());

        // Satu gambar untuk semua nominal; hapus dari satu nominal tidak menghapus file bersama
        $this->post(route('admin.brands.product-image', $brand->id), ['image' => UploadedFile::fake()->image('diamond.png')]);
        $shared = $product->refresh()->image;
        Storage::disk('public')->assertExists($shared);

        $second = Product::query()->create($product->only(['brand_id', 'category', 'base_price', 'selling_price']) + ['sku' => 'ML172', 'name' => '172 Diamond', 'image' => $shared]);
        $this->delete(route('admin.products.image.destroy', $product));
        Storage::disk('public')->assertExists($shared);
        $this->delete(route('admin.products.image.destroy', $second));
        Storage::disk('public')->assertMissing($shared);

        // Hapus banner
        $this->put(route('admin.brands.update', $brand->id), ['name' => 'Mobile Legends', 'field_label' => 'User ID', 'is_active' => 1, 'remove_banner' => 1]);
        $this->assertNull($brand->refresh()->banner);
        $this->assertNotNull($brand->image);

        $this->get(route('brands.show', $brand))->assertOk()->assertSee($brand->imageUrl());
    }

    public function test_flash_sale_price_quota_and_release(): void
    {
        [$brand, $method] = $this->makeOrderSetup();
        $product = Product::query()->where('sku', 'ML86')->first();
        $sale = FlashSale::query()->create([
            'product_id' => $product->id, 'price' => 18_000, 'quota' => 1,
            'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(), 'is_active' => true,
        ]);

        $this->get(route('home'))->assertSee('Flash Sale')->assertSee('Rp18.000');
        $this->get(route('brands.show', ['brand' => $brand, 'item' => $product->id]))->assertSee('Rp18.000')->assertSee('Sisa 1');

        $order = fn () => $this->post(route('orders.store', $brand), [
            'customer_id' => '1', 'zone_id' => '2', 'product_id' => $product->id,
            'payment_method_id' => $method->id, 'whatsapp' => '081234567890',
        ]);

        // Order pertama dapat harga flash sale dan memakai kuota
        $order();
        $first = Order::query()->latest('id')->first();
        $this->assertSame(18_000, $first->selling_price);
        $this->assertSame($sale->id, $first->flash_sale_id);
        $this->assertSame(1, $sale->refresh()->sold);

        // Kuota habis -> order berikutnya kembali ke harga normal
        $order();
        $second = Order::query()->latest('id')->first();
        $this->assertSame($product->selling_price, $second->selling_price);
        $this->assertNull($second->flash_sale_id);

        // Order pertama kedaluwarsa -> kuota kembali
        $this->travel(4)->hours();
        $sale->update(['ends_at' => now()->addHour()]);
        $this->artisan('orders:expire');
        $this->assertSame(0, $sale->refresh()->sold);
    }

    public function test_admin_banner_and_flash_sale_management(): void
    {
        [, $method] = $this->makeOrderSetup();
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.banners.index'))->assertOk()->assertSee('Belum ada banner');
        $this->post(route('admin.banners.store'), [
            'title' => 'Promo Oktober', 'image' => UploadedFile::fake()->image('promo.jpg', 1600, 600), 'is_active' => 1,
        ])->assertRedirect(route('admin.banners.index'));
        $banner = Banner::query()->first();
        Storage::disk('public')->assertExists($banner->image);
        $this->get(route('home'))->assertSee('Promo Oktober');

        $product = Product::query()->where('sku', 'ML86')->first();
        $this->get(route('admin.flash-sales.create', ['product' => $product->id]))->assertOk();
        $this->post(route('admin.flash-sales.store'), [
            'product_id' => $product->id, 'price' => 15_000, 'quota' => 5,
            'starts_at' => now()->format('Y-m-d\\TH:i'), 'ends_at' => now()->subHour()->format('Y-m-d\\TH:i'), 'is_active' => 1,
        ])->assertSessionHasErrors('ends_at');
        $this->post(route('admin.flash-sales.store'), [
            'product_id' => $product->id, 'price' => 15_000, 'quota' => 5,
            'starts_at' => now()->subMinute()->format('Y-m-d\\TH:i'), 'ends_at' => now()->addDay()->format('Y-m-d\\TH:i'), 'is_active' => 1,
        ])->assertSessionHasNoErrors();
        // Harga di bawah modal ditandai rugi
        $this->get(route('admin.flash-sales.index'))->assertOk()->assertSee('Rugi per item')->assertSee('Berlangsung');

        $this->delete(route('admin.banners.destroy', $banner));
        Storage::disk('public')->assertMissing($banner->image);
    }
}
