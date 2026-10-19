<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/** Uji ketahanan terhadap SQL injection & XSS dengan payload serangan nyata */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected const SQLI = ["' OR '1'='1", '1; DROP TABLE orders; --', "' UNION SELECT password FROM users --", '%\' AND 1=1 --'];

    protected const XSS = '<script>alert("xss")</script><img src=x onerror=alert(1)>';

    protected Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->brand = Brand::query()->create(['name' => 'Mobile Legends', 'slug' => 'mobile-legends', 'category' => 'Games', 'field_label' => 'User ID', 'is_active' => true]);
        Product::query()->create(['brand_id' => $this->brand->id, 'sku' => 'ML5', 'name' => 'Mobile Legends 5 Diamond', 'category' => 'Games', 'type' => 'Umum',
            'base_price' => 1000, 'selling_price' => 1500, 'is_active' => true, 'provider_active' => true, 'unlimited_stock' => true]);
        PaymentMethod::query()->create(['name' => 'BCA', 'type' => 'bank', 'account_number' => '1', 'account_name' => 'X', 'is_active' => true]);
    }

    public function test_public_search_is_safe_from_sql_injection_and_xss(): void
    {
        foreach (self::SQLI as $payload) {
            $this->get(route('home', ['q' => $payload]))->assertOk();
            $this->get(route('price-list', ['q' => $payload, 'kategori' => $payload]))->assertOk()->assertSee('Produk tidak ditemukan');
            $this->get(route('track', ['invoice' => $payload]))->assertRedirect(route('track'));
        }

        // Tabel masih utuh setelah payload DROP TABLE
        $this->assertSame(1, Product::query()->count());

        $this->get(route('price-list', ['q' => self::XSS]))->assertOk()
            ->assertDontSee(self::XSS, false)
            ->assertSee(e(self::XSS), false);
        $this->get(route('home', ['q' => self::XSS]))->assertOk()->assertDontSee('<script>alert("xss")</script>', false);
    }

    public function test_admin_search_filters_are_safe_from_sql_injection(): void
    {
        $this->actingAs(User::factory()->create());

        foreach (self::SQLI as $payload) {
            $this->get(route('admin.orders.index', ['q' => $payload, 'status' => $payload, 'payment_status' => $payload]))->assertOk();
            $this->get(route('admin.products.index', ['q' => $payload, 'category' => $payload, 'brand_id' => $payload]))->assertOk();
            $this->get(route('admin.brands.index', ['q' => $payload, 'category' => $payload]))->assertOk();
            $this->get(route('admin.api-logs.index', ['q' => $payload, 'provider' => $payload]))->assertOk();
        }

        $this->assertSame(1, User::query()->count());
    }

    public function test_order_form_rejects_injection_payloads(): void
    {
        $product = Product::query()->first();

        $this->post(route('orders.store', $this->brand), [
            'customer_id' => self::XSS, 'product_id' => $product->id,
            'payment_method_id' => PaymentMethod::query()->value('id'), 'whatsapp' => "0812' OR 1=1 --",
        ])->assertSessionHasErrors(['customer_id', 'whatsapp']);

        $this->post(route('orders.store', $this->brand), [
            'customer_id' => '123', 'product_id' => '1 OR 1=1', 'payment_method_id' => '1; DROP TABLE orders', 'whatsapp' => '081234567890',
        ])->assertSessionHasErrors(['product_id', 'payment_method_id']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_admin_entered_content_is_escaped_on_public_pages(): void
    {
        Setting::put(['announcement' => self::XSS, 'site_tagline' => self::XSS]);
        $this->brand->update(['description' => self::XSS, 'seo_title' => '</title>'.self::XSS]);

        $html = $this->get(route('brands.show', $this->brand))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert("xss")</script>', $html);
        $this->assertStringNotContainsString('<img src=x onerror', $html);
        // JSON-LD tidak bisa "keluar" dari tag <script> karena < dan > di-escape
        $this->assertStringNotContainsString('</title><script>', $html);
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_svg_uploads_are_rejected_everywhere(): void
    {
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        // Aturan 'image' bawaan Laravel tidak menerima SVG (dipakai di brand, banner, produk, bukti transfer)
        $this->assertTrue(Validator::make(['f' => $svg], ['f' => 'image'])->fails());
    }
}
