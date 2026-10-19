<?php

namespace Tests\Feature;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PaymentMethodActivationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config([
            'services.duitku.merchant_code' => 'DS1234',
            'services.duitku.api_key' => 'duitku-key',
            'services.duitku.sandbox' => true,
        ]);
        $this->actingAs(User::factory()->create());
    }

    /** Duitku palsu: BCA VA, OVO, QRIS ShopeePay, Indomaret */
    protected function fakeDuitku(string $contentType = 'image/png', string $body = 'PNGDATA'): void
    {
        Http::fake([
            '*/paymentmethod/getpaymentmethod' => Http::response([
                'paymentFee' => [
                    ['paymentMethod' => 'BC', 'paymentName' => 'BCA VA', 'paymentImage' => 'https://images.duitku.com/hotlink-ok/BCA.PNG', 'totalFee' => '4000'],
                    ['paymentMethod' => 'OV', 'paymentName' => 'OVO', 'paymentImage' => 'https://images.duitku.com/hotlink-ok/OV.PNG', 'totalFee' => '0'],
                    ['paymentMethod' => 'SP', 'paymentName' => 'SHOPEEPAY QRIS', 'paymentImage' => 'https://images.duitku.com/hotlink-ok/SHOPEEPAY.PNG', 'totalFee' => '0'],
                    ['paymentMethod' => 'IR', 'paymentName' => 'INDOMARET', 'paymentImage' => 'https://images.duitku.com/hotlink-ok/IR.PNG', 'totalFee' => '0'],
                ],
                'responseCode' => '00',
                'responseMessage' => 'SUCCESS',
            ]),
            'images.duitku.com/*' => Http::response($body, 200, ['Content-Type' => $contentType]),
        ]);
    }

    public function test_halaman_menampilkan_daftar_centang_dari_duitku(): void
    {
        $this->fakeDuitku();

        $this->get(route('admin.payment-methods.index'))
            ->assertOk()
            ->assertSee('Pembayaran Otomatis (Duitku)')
            ->assertSee('VA BCA')
            ->assertSee('Biaya Duitku Rp4.000')
            ->assertSee('name="codes[]" value="OV"', false);

        Http::assertSent(fn (Request $r) => str_ends_with($r->url(), '/paymentmethod/getpaymentmethod')
            && $r['merchantcode'] === 'DS1234'
            && $r['amount'] === 10_000
            && $r['signature'] === hash_hmac('sha256', 'DS1234'.'10000'.$r['datetime'], 'duitku-key'));
    }

    public function test_centang_membuat_metode_lengkap_dengan_jenis_dan_logo(): void
    {
        $this->fakeDuitku();

        $this->post(route('admin.payment-methods.activate'), ['codes' => ['BC', 'OV', 'SP', 'IR']])
            ->assertRedirect()
            ->assertSessionHas('toast', fn ($t) => $t[0] === 'success');

        $this->assertSame(
            ['BC' => 'bank', 'OV' => 'ewallet', 'SP' => 'qris', 'IR' => 'retail'],
            PaymentMethod::query()->orderBy('sort_order')->pluck('type', 'gateway_code')->all(),
        );
        $bca = PaymentMethod::query()->where('gateway_code', 'BC')->firstOrFail();
        $this->assertSame('VA BCA', $bca->name);
        $this->assertTrue($bca->is_active);
        $this->assertSame(0, $bca->fee);
        $this->assertSame("payment-methods/duitku/{$bca->id}-BC.png", $bca->image);
        Storage::disk('public')->assertExists($bca->image);
    }

    public function test_hapus_centang_menonaktifkan_tanpa_menghapus_dan_metode_manual_aman(): void
    {
        $this->fakeDuitku();
        $manual = PaymentMethod::query()->create(['name' => 'Transfer BRI', 'type' => 'bank', 'account_number' => '123', 'account_name' => 'Toko', 'is_active' => true]);
        $this->post(route('admin.payment-methods.activate'), ['codes' => ['BC', 'OV']]);

        $this->post(route('admin.payment-methods.activate'), ['codes' => ['OV']])->assertSessionHas('toast', fn ($t) => $t[0] === 'success');

        $this->assertFalse(PaymentMethod::query()->where('gateway_code', 'BC')->firstOrFail()->is_active);
        $this->assertTrue(PaymentMethod::query()->where('gateway_code', 'OV')->firstOrFail()->is_active);
        $this->assertTrue($manual->refresh()->is_active);
        $this->assertSame(3, PaymentMethod::query()->count());

        // Centang ulang tidak membuat duplikat
        $this->post(route('admin.payment-methods.activate'), ['codes' => ['BC', 'OV']]);
        $this->assertSame(1, PaymentMethod::query()->where('gateway_code', 'BC')->count());
        $this->assertTrue(PaymentMethod::query()->where('gateway_code', 'BC')->first()->is_active);
    }

    public function test_logo_unggahan_admin_tidak_ditimpa(): void
    {
        $this->fakeDuitku();
        $method = PaymentMethod::query()->create(['name' => 'BCA', 'type' => 'bank', 'gateway_code' => 'BC', 'image' => 'payment-methods/custom.png', 'is_active' => false]);

        $this->post(route('admin.payment-methods.activate'), ['codes' => ['BC']]);

        $this->assertSame('payment-methods/custom.png', $method->refresh()->image);
        $this->assertTrue($method->is_active);
    }

    public function test_kode_di_luar_daftar_duitku_ditolak(): void
    {
        $this->fakeDuitku();

        $this->post(route('admin.payment-methods.activate'), ['codes' => ['XX']])->assertSessionHasErrors('codes.0');
        $this->assertSame(0, PaymentMethod::query()->count());
    }

    public function test_duitku_gagal_tidak_mengubah_apa_pun(): void
    {
        Http::fake(['*' => Http::response(['Message' => 'Merchant not found'], 401)]);
        $method = PaymentMethod::query()->create(['name' => 'BCA', 'type' => 'bank', 'gateway_code' => 'BC', 'is_active' => true]);

        $this->get(route('admin.payment-methods.index'))->assertOk()->assertSee('Gagal memuat daftar metode dari Duitku');
        $this->post(route('admin.payment-methods.activate'), ['codes' => []])->assertSessionHas('toast', fn ($t) => $t[0] === 'error');

        $this->assertTrue($method->refresh()->is_active);
    }

    public function test_svg_bersih_diterima(): void
    {
        $this->fakeDuitku('image/svg+xml', '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><use xlink:href="#a"/><path d="M0 0"/></svg>');

        $this->post(route('admin.payment-methods.activate'), ['codes' => ['BC']])->assertSessionHas('toast', fn ($t) => $t[0] === 'success');

        $this->assertStringEndsWith('-BC.svg', PaymentMethod::query()->firstOrFail()->image);
    }

    public static function svgBerbahaya(): array
    {
        return [
            'script' => ['<svg><script>alert(1)</script></svg>'],
            'atribut event' => ['<svg onload="alert(1)"></svg>'],
            'javascript:' => ['<svg><a href="javascript:alert(1)"/></svg>'],
            'tautan eksternal' => ['<svg><image href="https://evil.test/x.png"/></svg>'],
            'foreignObject' => ['<svg><foreignObject><p>x</p></foreignObject></svg>'],
        ];
    }

    #[DataProvider('svgBerbahaya')]
    public function test_svg_berbahaya_ditolak_dan_memakai_logo_bawaan(string $svg): void
    {
        $this->fakeDuitku('image/svg+xml', $svg);

        $this->post(route('admin.payment-methods.activate'), ['codes' => ['BC']])->assertSessionHas('toast', fn ($t) => $t[0] === 'warning');

        $method = PaymentMethod::query()->firstOrFail();
        $this->assertTrue($method->is_active);
        $this->assertNull($method->image);
        $this->assertStringEndsWith('images/payments/BC.svg', $method->logoUrl());
    }

    public function test_tanpa_kredensial_menampilkan_petunjuk(): void
    {
        config(['services.duitku.api_key' => null]);

        $this->get(route('admin.payment-methods.index'))->assertOk()->assertSee('Duitku belum terhubung');
        $this->post(route('admin.payment-methods.activate'), ['codes' => []])->assertSessionHas('toast', fn ($t) => $t[0] === 'error');
    }
}
