<?php

namespace App\Support;

class Faq
{
    /** Pertanyaan umum — dipakai di beranda dan halaman Cara Order */
    public static function items(): array
    {
        return [
            ['Berapa lama proses top up?', 'Setelah pembayaran dikonfirmasi admin, pesanan diproses otomatis dan umumnya masuk dalam 1–5 menit. Konfirmasi pembayaran dilakukan pada jam operasional admin.'],
            ['Kenapa nominal transfer ada kode unik?', 'Kode unik (3 digit terakhir) membantu kami mencocokkan transfer Anda dengan pesanan secara cepat dan akurat. Pastikan transfer sesuai nominal hingga digit terakhir.'],
            ['Bagaimana jika saya salah memasukkan ID?', 'Top up yang sudah berhasil ke ID yang salah tidak dapat dibatalkan. Periksa kembali ID dan Zone/Server sebelum membayar. Jika pesanan belum diproses, segera hubungi CS.'],
            ['Pesanan gagal, apakah uang saya kembali?', 'Ya. Jika transaksi gagal dari penyedia, admin akan memproses ulang atau mengembalikan dana Anda 100% ke rekening/e-wallet asal.'],
            ['Bagaimana cara cek status pesanan?', 'Buka menu Cek Transaksi lalu masukkan nomor invoice Anda. Halaman invoice juga diperbarui otomatis saat pesanan diproses.'],
            ['Apakah saya perlu membuat akun?', 'Tidak perlu. Cukup isi ID game/nomor tujuan dan nomor WhatsApp, lalu simpan nomor invoice untuk mengecek status pesanan.'],
        ];
    }
}
