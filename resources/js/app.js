import $ from 'jquery';
import select2 from 'select2';
import 'select2/dist/css/select2.min.css';

// Paket select2 (UMD) memasang dirinya ke instance jQuery yang diberikan
select2(window, $);
window.$ = window.jQuery = $;

// Teks antarmuka Select2 dalam Bahasa Indonesia
const bahasa = {
    noResults: () => 'Tidak ada hasil yang cocok',
    searching: () => 'Mencari…',
    loadingMore: () => 'Memuat lebih banyak…',
    removeAllItems: () => 'Hapus semua',
    inputTooShort: (args) => `Ketik minimal ${args.minimum - args.input.length} karakter lagi`,
};

/**
 * Ubah semua <select class="input"> menjadi Select2.
 * Tambahkan atribut data-native pada <select> yang ingin tetap memakai dropdown bawaan browser.
 */
function initSelect2(root = document) {
    $(root).find('select.input:not([data-native])').each(function () {
        const $select = $(this);
        if ($select.hasClass('select2-hidden-accessible')) return;

        const $empty = $select.find('option[value=""]').first();
        const placeholder = $empty.text() || 'Pilih…';

        $select.select2({
            width: '100%',
            language: bahasa,
            placeholder,
            // Opsi kosong (mis. "Semua") bisa dipilih lagi lewat tombol ×, kecuali field wajib
            allowClear: $empty.length > 0 && !$select.prop('required'),
            // Kotak pencarian hanya muncul bila pilihannya banyak
            minimumResultsForSearch: $select.find('option').length > 8 ? 0 : Infinity,
            dropdownAutoWidth: false,
        });

        // Tampilan error validasi ikut ke Select2
        $select.next('.select2-container').toggleClass('select2-is-error', $select.hasClass('input-error'));
    });
}

// Select2 memicu event "change" versi jQuery yang tidak ditangkap addEventListener;
// teruskan sebagai event native agar skrip halaman (hitung harga, toggle field) tetap jalan
$(document).on('select2:select select2:clear', (e) => {
    e.target.dispatchEvent(new Event('change', { bubbles: true }));
});

// Fokuskan kotak pencarian otomatis saat dropdown dibuka
$(document).on('select2:open', () => {
    document.querySelector('.select2-container--open .select2-search__field')?.focus();
});

document.addEventListener('DOMContentLoaded', () => initSelect2());
window.initSelect2 = initSelect2;
