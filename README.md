# elhazmi.com

Website top-up game & produk digital (Laravel 13) yang terhubung ke **Digiflazz**.
Pembayaran masih **manual** (transfer bank / e-wallet / QRIS statis + konfirmasi admin), dan sudah disiapkan agar payment gateway bisa ditambahkan belakangan.

## Alur Transaksi

1. Pembeli memilih produk → isi ID → pilih nominal & metode bayar → dapat invoice dengan **kode unik**.
2. Pembeli transfer sesuai total, lalu unggah bukti di halaman invoice.
3. Admin mencocokkan mutasi → klik **Konfirmasi Lunas & Proses** → order otomatis dikirim ke Digiflazz.
4. Status diperbarui lewat **webhook** Digiflazz (cadangan: cek otomatis tiap 10 menit, atau tombol *Cek Status*).
5. Jika gagal: admin bisa **Ulangi Transaksi** (ref_id baru) atau **Tandai Sudah Refund**.

## Instalasi Lokal (Herd)

```bash
composer install
npm install && npm run build
cp .env.example .env   # lalu isi kredensial
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

Buka `https://topup-cdnesia.test`, panel admin di `/admin` (akun dari `ADMIN_EMAIL` / `ADMIN_PASSWORD` di `.env`, bawaan `admin@elhazmi.com` / `password` — **segera ganti**).

## Konfigurasi Digiflazz (`.env`)

| Variabel | Keterangan |
|---|---|
| `DIGIFLAZZ_USERNAME` | Username API di Digiflazz → Atur Koneksi → API |
| `DIGIFLAZZ_KEY` | Development key (uji coba) atau Production key |
| `DIGIFLAZZ_TESTING` | `true` = transaksi simulasi, tidak memotong saldo |
| `DIGIFLAZZ_WEBHOOK_SECRET` | Secret webhook, harus sama dengan di dashboard Digiflazz |

Langkah di dashboard Digiflazz:
1. **Whitelist IP** server Anda (wajib, request dari IP lain ditolak).
2. Atur **Webhook URL** ke `https://domainanda.com/webhook/digiflazz` + secret.
3. Isi **saldo deposit**.

Setelah itu: Admin → Produk & Harga → **Sinkron Digiflazz**.

## Perintah & Penjadwalan

| Perintah | Fungsi | Jadwal |
|---|---|---|
| `php artisan digiflazz:sync` | Tarik produk & harga terbaru | Tiap jam |
| `php artisan orders:expire` | Batalkan order yang lewat batas bayar | Tiap 5 menit |
| `php artisan orders:check-pending` | Cek ulang transaksi yang masih diproses | Tiap 10 menit |

Di server produksi, tambahkan cron:

```
* * * * * cd /path/ke/project && php artisan schedule:run >> /dev/null 2>&1
```

## Menambahkan Payment Gateway Nanti

Semua jalur pembayaran bermuara ke `App\Services\OrderService::markAsPaid()`.
Untuk Tripay/Duitku/Midtrans cukup: buat invoice di gateway saat order dibuat, lalu di controller callback gateway (setelah verifikasi signature) panggil `markAsPaid($order)`.

## Struktur Penting

- `app/Services/Digiflazz/DigiflazzClient.php` — klien API (saldo, price list, transaksi, verifikasi webhook)
- `app/Services/ProductSyncService.php` — sinkronisasi produk & brand
- `app/Services/OrderService.php` — alur order, pembayaran, pengiriman ke Digiflazz
- `app/Services/PricingService.php` — rumus harga jual (margin % + margin tetap + pembulatan)

## Tes

```bash
php artisan test
```
# APLIKASI-TOPUP-LARAVEL
