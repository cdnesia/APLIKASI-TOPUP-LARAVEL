@php($site = \App\Models\Setting::get('site_name'))
<x-legal-page title="Kebijakan Privasi" :sections="[
    ['Data yang Kami Kumpulkan', [
        'Data tujuan transaksi: ID akun game, Zone/Server, nomor HP, atau nomor meter.',
        'Nomor WhatsApp untuk keperluan informasi pesanan.',
        'Bukti pembayaran yang Anda unggah.',
        'Data teknis standar seperti alamat IP untuk keamanan sistem.',
    ]],
    ['Penggunaan Data', [
        'Memproses dan mengirimkan produk yang Anda pesan.',
        'Memverifikasi pembayaran dan menangani kendala atau pengembalian dana.',
        'Menghubungi Anda terkait status pesanan bila diperlukan.',
    ]],
    ['Perlindungan Data', [
        'Bukti pembayaran disimpan di penyimpanan privat yang hanya dapat diakses admin.',
        'Kami tidak pernah meminta password akun game, PIN, atau kode OTP Anda.',
        'Data tujuan hanya dibagikan kepada distributor produk sebatas yang diperlukan untuk memproses transaksi.',
    ]],
    ['Hak Anda', [
        'Anda dapat meminta informasi atau penghapusan data pesanan Anda dengan menghubungi CS kami.',
        'Kami tidak menjual data pribadi Anda kepada pihak mana pun.',
    ]],
]">
    {{ $site }} menghargai privasi Anda. Kebijakan ini menjelaskan data apa yang kami kumpulkan dan bagaimana kami menggunakannya.
</x-legal-page>
