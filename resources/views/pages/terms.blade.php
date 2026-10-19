@php($site = \App\Models\Setting::get('site_name'))
<x-legal-page title="Syarat & Ketentuan" :sections="[
    ['Layanan', [
        $site.' menyediakan layanan pembelian produk digital seperti top up game, pulsa, paket data, token listrik, dan saldo e-wallet.',
        'Dengan melakukan pemesanan, Anda dianggap telah membaca dan menyetujui syarat & ketentuan ini.',
    ]],
    ['Pemesanan', [
        'Pembeli bertanggung jawab penuh atas kebenaran data tujuan (ID akun, Zone/Server, nomor HP, nomor meter).',
        'Kesalahan input data oleh pembeli yang menyebabkan produk terkirim ke tujuan yang salah bukan tanggung jawab kami.',
        'Harga dapat berubah sewaktu-waktu mengikuti harga distributor. Harga yang berlaku adalah harga saat pesanan dibuat.',
    ]],
    ['Pembayaran', [
        'Pembayaran harus dilakukan sesuai total yang tertera, termasuk kode unik, sebelum batas waktu pembayaran.',
        'Pesanan yang tidak dibayar hingga batas waktu akan dibatalkan otomatis.',
        'Bukti pembayaran palsu atau manipulasi akan menyebabkan pesanan dibatalkan dan dapat dilaporkan kepada pihak berwenang.',
    ]],
    ['Pemrosesan & Pengembalian Dana', [
        'Pesanan diproses setelah pembayaran terverifikasi, pada jam operasional admin.',
        'Jika transaksi gagal dari pihak distributor, kami akan memproses ulang atau mengembalikan dana secara penuh.',
        'Pengembalian dana dilakukan ke rekening/e-wallet yang dikonfirmasi bersama pembeli.',
        'Transaksi yang telah berstatus Sukses tidak dapat dibatalkan atau dikembalikan.',
    ]],
    ['Lain-lain', [
        'Kami berhak menolak atau membatalkan pesanan yang terindikasi penipuan atau penyalahgunaan.',
        'Syarat & ketentuan ini dapat diperbarui sewaktu-waktu tanpa pemberitahuan sebelumnya.',
    ]],
]">
    Halaman ini mengatur ketentuan penggunaan layanan {{ $site }}. Mohon dibaca dengan saksama sebelum melakukan pemesanan.
</x-legal-page>
