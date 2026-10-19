<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentMethodStatusTest extends TestCase
{
    use RefreshDatabase;

    protected Brand $brand;

    protected Product $product;

    protected PaymentMethod $bca;

    protected PaymentMethod $ovo;

    protected PaymentMethod $manual;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.digiflazz.username' => 'tester',
            'services.digiflazz.key' => 'secret-key',
            'services.duitku.merchant_code' => 'DS1234',
            'services.duitku.api_key' => 'duitku-key',
            'services.duitku.sandbox' => true,
        ]);
        Setting::put(['margin_percent' => '10', 'margin_flat' => '0', 'price_rounding' => '100']);

        Http::fake(['*/price-list' => Http::response(['data' => [[
            'product_name' => 'MOBILE LEGENDS 86 Diamond', 'category' => 'Games', 'brand' => 'MOBILE LEGENDS',
            'type' => 'Umum', 'price' => 19_250, 'buyer_sku_code' => 'ML86', 'buyer_product_status' => true,
            'seller_product_status' => true, 'unlimited_stock' => true, 'stock' => 0, 'desc' => '-',
        ]]])]);
        $this->artisan('digiflazz:sync')->assertSuccessful();

        $this->brand = Brand::query()->where('slug', 'mobile-legends-games')->firstOrFail();
        $this->product = Product::query()->where('sku', 'ML86')->firstOrFail();
        $this->bca = PaymentMethod::query()->create(['name' => 'VA BCA', 'type' => 'bank', 'gateway_code' => 'BC', 'is_active' => true, 'sort_order' => 1]);
        $this->ovo = PaymentMethod::query()->create(['name' => 'OVO', 'type' => 'ewallet', 'gateway_code' => 'OV', 'is_active' => true, 'sort_order' => 2]);
        $this->manual = PaymentMethod::query()->create(['name' => 'Transfer BRI', 'type' => 'bank', 'account_number' => '123', 'account_name' => 'Toko', 'is_active' => true, 'sort_order' => 3]);
    }

    /** Duitku palsu: hanya OVO yang sedang tersedia, BCA offline */
    protected function fakeOnlyOvoOnline(): void
    {
        Http::fake(['*/paymentmethod/getpaymentmethod' => Http::response([
            'paymentFee' => [['paymentMethod' => 'OV', 'paymentName' => 'OVO', 'paymentImage' => 'https://images.duitku.com/hotlink-ok/OV.PNG', 'totalFee' => '0']],
            'responseCode' => '00',
            'responseMessage' => 'SUCCESS',
        ])]);
    }

    protected function order(PaymentMethod $method)
    {
        return $this->post(route('orders.store', $this->brand), [
            'customer_id' => '12345678', 'zone_id' => '1234', 'product_id' => $this->product->id,
            'payment_method_id' => $method->id, 'whatsapp' => '081234567890',
        ]);
    }

    public function test_halaman_order_menandai_online_dan_offline(): void
    {
        $this->fakeOnlyOvoOnline();

        $html = $this->get(route('brands.show', $this->brand))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/name="payment_method_id" value="'.$this->bca->id.'"[^>]*data-offline disabled/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="payment_method_id" value="'.$this->ovo->id.'"[^>]*data-offline/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="payment_method_id" value="'.$this->manual->id.'"[^>]*data-offline/', $html);
        $this->assertStringContainsString('Sedang gangguan, pilih metode lain', $html);
        // Metode offline ditaruh paling bawah
        $this->assertGreaterThan(strpos($html, 'name="payment_method_id" value="'.$this->manual->id.'"'), strpos($html, 'name="payment_method_id" value="'.$this->bca->id.'"'));
    }

    public function test_pesanan_dengan_metode_offline_ditolak(): void
    {
        $this->fakeOnlyOvoOnline();

        $this->order($this->bca)->assertSessionHasErrors(['payment_method_id' => 'VA BCA sedang offline. Silakan pilih metode pembayaran lain.']);
        $this->assertSame(0, Order::query()->count());
    }

    public function test_duitku_gangguan_tidak_memblokir_pilihan(): void
    {
        Http::fake(['*/paymentmethod/getpaymentmethod' => Http::response(['Message' => 'Server error'], 500)]);

        $html = $this->get(route('brands.show', $this->brand))->assertOk()->getContent();

        $this->assertStringNotContainsString('data-offline disabled', $html);
        $this->assertStringNotContainsString('>Online<', $html);

        // Status gagal di-cache sebentar: kunjungan berikutnya tidak memanggil Duitku lagi
        $this->get(route('brands.show', $this->brand))->assertOk();
        $this->assertCount(1, Http::recorded(fn ($request) => str_contains($request->url(), 'getpaymentmethod')));
    }
}
