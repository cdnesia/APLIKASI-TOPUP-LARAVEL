<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Product;
use App\Services\Digiflazz\DigiflazzClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSyncService
{
    public function __construct(
        protected DigiflazzClient $digiflazz,
        protected PricingService $pricing,
    ) {}

    /**
     * Sinkronkan price list Digiflazz ke tabel brands & products.
     *
     * @return array{created:int, updated:int, deactivated:int}
     */
    public function sync(): array
    {
        $items = $this->digiflazz->priceList();
        $stats = ['created' => 0, 'updated' => 0, 'deactivated' => 0];
        $now = now();

        DB::transaction(function () use ($items, $now, &$stats) {
            $brands = [];
            $seenSkus = [];

            foreach ($items as $item) {
                $sku = $item['buyer_sku_code'] ?? null;
                if (! $sku) {
                    continue;
                }
                $seenSkus[] = $sku;

                $brandKey = $item['category'].'|'.$item['brand'];
                $brands[$brandKey] ??= $this->findOrCreateBrand($item['brand'], $item['category']);

                $product = Product::query()->firstOrNew(['sku' => $sku]);
                $basePrice = (int) $item['price'];

                $product->fill([
                    'brand_id' => $brands[$brandKey]->id,
                    'name' => $item['product_name'],
                    'category' => $item['category'],
                    'type' => $item['type'] ?? null,
                    'base_price' => $basePrice,
                    'provider_active' => (bool) $item['buyer_product_status'] && (bool) $item['seller_product_status'],
                    'unlimited_stock' => (bool) ($item['unlimited_stock'] ?? true),
                    'stock' => $item['stock'] ?? null,
                    'description' => $item['desc'] ?? null,
                    'synced_at' => $now,
                ]);

                if (! $product->price_locked) {
                    $product->selling_price = $this->pricing->sellingPrice($basePrice);
                } elseif ($product->selling_price < $basePrice) {
                    // Harga manual di bawah modal -> naikkan agar tidak rugi
                    $product->selling_price = $this->pricing->sellingPrice($basePrice);
                }

                $stats[$product->exists ? 'updated' : 'created']++;
                $product->save();
            }

            // Produk yang sudah tidak ada di Digiflazz dinonaktifkan, bukan dihapus (riwayat order tetap aman)
            $stats['deactivated'] = Product::query()
                ->whereNotIn('sku', $seenSkus)
                ->where('provider_active', true)
                ->update(['provider_active' => false]);
        });

        return $stats;
    }

    protected function findOrCreateBrand(string $name, string $category): Brand
    {
        $slug = Str::slug($name === $category ? $name : "{$name} {$category}");

        // Brand yang sama di kategori berbeda (mis. TELKOMSEL di Pulsa & Data) dipisah
        return Brand::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $this->displayName($name), 'category' => $category] + $this->defaultInput($name, $category),
        );
    }

    /** "MOBILE LEGENDS" -> "Mobile Legends", tetapi nama singkat (XL, PLN, BY.U, DANA) tetap kapital */
    public function displayName(string $name): string
    {
        return mb_strlen($name) <= 4 ? Str::upper($name) : Str::title($name);
    }

    /** Label input bawaan berdasarkan jenis produk — bisa diubah admin */
    protected function defaultInput(string $name, string $category): array
    {
        $name = Str::upper($name);

        return match (true) {
            str_contains($name, 'MOBILE LEGENDS') => ['field_label' => 'User ID', 'zone_label' => 'Zone ID', 'input_hint' => 'Contoh: 12345678 (1234)'],
            in_array($category, ['Games', 'Voucher']) => ['field_label' => 'User ID', 'input_hint' => 'Lihat User ID di profil akun game Anda'],
            in_array($category, ['Pulsa', 'Data', 'Paket SMS & Telpon', 'Masa Aktif']) => ['field_label' => 'Nomor HP', 'input_hint' => 'Contoh: 081234567890'],
            $category === 'PLN' => ['field_label' => 'Nomor Meter / ID Pelanggan', 'input_hint' => 'Tertera di meteran listrik Anda'],
            $category === 'E-Money' => ['field_label' => 'Nomor HP Terdaftar', 'input_hint' => 'Nomor HP akun e-wallet Anda'],
            default => ['field_label' => 'ID Pelanggan'],
        };
    }
}
