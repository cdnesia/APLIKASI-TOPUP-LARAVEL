<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Brand;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DuitkuPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected Brand $brand;

    protected Product $product;

    protected PaymentMethod $method;

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
        $this->method = PaymentMethod::query()->create([
            'name' => 'QRIS', 'type' => 'qris', 'gateway_code' => 'SP', 'fee' => 500, 'is_active' => true,
        ]);
    }

    protected function placeOrder(): TestResponse
    {
        return $this->post(route('orders.store', $this->brand), [
            'customer_id' => '12345678',
            'zone_id' => '1234',
            'product_id' => $this->product->id,
            'payment_method_id' => $this->method->id,
            'whatsapp' => '081234567890',
        ]);
    }

    protected function callbackPayload(Order $order, array $overrides = []): array
    {
        $payload = [
            'merchantCode' => 'DS1234',
            'amount' => (string) $order->total,
            'merchantOrderId' => $order->invoice,
            'resultCode' => '00',
            'reference' => 'DS1234REF',
        ] + $overrides;

        return $payload + ['signature' => hash_hmac('sha256', $payload['merchantCode'].$payload['amount'].$payload['merchantOrderId'], 'duitku-key')];
    }

    public function test_automatic_order_creates_duitku_invoice_without_unique_code(): void
    {
        Http::fake(['*/v2/inquiry' => Http::response([
            'merchantCode' => 'DS1234', 'reference' => 'DS1234REF', 'paymentUrl' => 'https://sandbox.duitku.com/pay/xyz',
            'vaNumber' => '', 'qrString' => '00020101021226QRISDUMMY', 'amount' => '21700', 'statusCode' => '00', 'statusMessage' => 'SUCCESS',
        ])]);

        $this->placeOrder()->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertSame(0, $order->unique_code);
        $this->assertSame($this->product->selling_price + 500, $order->total);
        $this->assertSame('DS1234REF', $order->gateway_reference);
        $this->assertSame('00020101021226QRISDUMMY', $order->qr_string);
        $this->assertFalse($order->canUploadProof());

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/v2/inquiry')
            && $request['paymentMethod'] === 'SP'
            && $request['paymentAmount'] === $order->total
            && $request['callbackUrl'] === route('webhook.duitku')
            && $request['signature'] === hash_hmac('sha256', 'DS1234'.$order->invoice.$order->total, 'duitku-key'));

        $this->get(route('orders.show', $order))->assertOk()->assertSee('QRISDUMMY')->assertDontSee('Unggah Bukti');
    }

    public function test_failed_invoice_cancels_order_and_returns_with_error(): void
    {
        Http::fake(['*/v2/inquiry' => Http::response(['Message' => 'Payment channel not available'], 400)]);

        $this->from(route('brands.show', $this->brand))->placeOrder()
            ->assertRedirect(route('brands.show', $this->brand))
            ->assertSessionHas('toast');

        $order = Order::query()->firstOrFail();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertStringContainsString('Payment channel not available', $order->admin_note);
    }

    public function test_va_below_minimum_is_rejected_before_order_is_created(): void
    {
        $this->product->update(['selling_price' => 2_000]);
        $this->method->update(['gateway_code' => 'BC', 'fee' => 0]);
        Http::fake();

        $this->from(route('brands.show', $this->brand))->placeOrder()
            ->assertRedirect(route('brands.show', $this->brand))
            ->assertSessionHasErrors('payment_method_id');

        $this->assertSame(0, Order::query()->count());
        Http::assertNothingSent();
        $this->get(route('brands.show', $this->brand))->assertSee('data-min="10000"', false);
    }

    public function test_valid_callback_marks_paid_and_sends_to_digiflazz(): void
    {
        Http::fake(['*/v2/inquiry' => Http::response(['reference' => 'DS1234REF', 'paymentUrl' => 'https://x', 'statusCode' => '00'])]);
        $this->placeOrder();
        $order = Order::query()->firstOrFail();

        Http::fake([
            '*/transactionStatus' => Http::response(['merchantOrderId' => $order->invoice, 'reference' => 'DS1234REF', 'amount' => (string) $order->total, 'statusCode' => '00']),
            '*/transaction' => Http::response(['data' => ['ref_id' => $order->invoice, 'status' => 'Sukses', 'rc' => '00', 'message' => 'OK', 'sn' => 'SN123', 'price' => 19_250]]),
        ]);

        $this->post(route('webhook.duitku'), $this->callbackPayload($order))->assertOk()->assertSee('SUCCESS');

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Success, $order->status);
        $this->assertSame('SN123', $order->sn);

        // Callback ganda tidak memproses ulang transaksi
        $this->post(route('webhook.duitku'), $this->callbackPayload($order))->assertOk();
        Http::assertSentCount(2);
    }

    public function test_callback_with_invalid_signature_is_rejected(): void
    {
        Http::fake(['*/v2/inquiry' => Http::response(['reference' => 'R', 'statusCode' => '00'])]);
        $this->placeOrder();
        $order = Order::query()->firstOrFail();

        $this->post(route('webhook.duitku'), ['signature' => 'palsu'] + $this->callbackPayload($order))->assertForbidden();
        $this->assertSame(PaymentStatus::Unpaid, $order->refresh()->payment_status);
    }

    public function test_callback_is_ignored_when_duitku_status_is_not_paid(): void
    {
        Http::fake(['*/v2/inquiry' => Http::response(['reference' => 'R', 'statusCode' => '00'])]);
        $this->placeOrder();
        $order = Order::query()->firstOrFail();

        Http::fake(['*/transactionStatus' => Http::response(['amount' => (string) $order->total, 'statusCode' => '01'])]);

        $this->post(route('webhook.duitku'), $this->callbackPayload($order))->assertOk();
        $this->assertSame(PaymentStatus::Unpaid, $order->refresh()->payment_status);
    }

    public function test_customer_can_check_payment_status_manually(): void
    {
        Http::fake(['*/v2/inquiry' => Http::response(['reference' => 'R', 'vaNumber' => '7007014001234567', 'statusCode' => '00'])]);
        $this->placeOrder();
        $order = Order::query()->firstOrFail();
        $this->get(route('orders.show', $order))->assertSee('7007014001234567');

        Http::fake([
            '*/transactionStatus' => Http::response(['amount' => (string) $order->total, 'statusCode' => '00']),
            '*/transaction' => Http::response(['data' => ['ref_id' => $order->invoice, 'status' => 'Pending', 'rc' => '03', 'message' => 'Pending']]),
        ]);

        $this->post(route('orders.check-payment', $order))->assertRedirect();

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Processing, $order->status);
    }
}
