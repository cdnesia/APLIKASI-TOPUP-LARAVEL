{{-- Toast: HP di tengah bawah (di atas navigasi bawah), desktop di pojok kanan bawah; hilang otomatis setelah 3 detik --}}
<div id="toast-container" class="pointer-events-none fixed inset-x-4 bottom-20 z-50 flex flex-col items-center gap-2 md:inset-x-auto md:right-5 md:bottom-5 md:items-end" aria-live="polite"></div>

<script>
    function showToast(message, type = 'success') {
        const colors = { success: 'bg-green-600', error: 'bg-red-600', warning: 'bg-yellow-500', info: 'bg-blue-600' };
        const hidden = 'opacity-0 translate-y-3 md:translate-y-0 md:translate-x-full';
        const toast = document.createElement('div');
        toast.className = `${colors[type] ?? colors.info} text-white text-sm font-medium px-4 py-3 rounded-lg shadow-lg flex items-center justify-center gap-2 text-center w-full max-w-sm md:w-auto md:justify-start md:text-left md:min-w-[260px] pointer-events-auto transition-all duration-300 ${hidden}`;
        toast.textContent = message;
        document.getElementById('toast-container').appendChild(toast);
        requestAnimationFrame(() => toast.classList.remove(...hidden.split(' ')));
        setTimeout(() => {
            toast.classList.add(...hidden.split(' '));
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    /**
     * Salin teks ke clipboard. Clipboard API butuh HTTPS & sering ditolak di Safari iOS / browser
     * dalam aplikasi (WhatsApp, Instagram), jadi ada cadangan execCommand lewat textarea tersembunyi.
     */
    async function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch {}
        }
        const area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', '');
        area.style.cssText = 'position:fixed;top:0;left:0;opacity:0;font-size:16px';
        document.body.appendChild(area);
        area.select();
        area.setSelectionRange(0, text.length);
        let ok = false;
        try { ok = document.execCommand('copy'); } catch {}
        area.remove();
        return ok;
    }

    // Semua tombol [data-copy] di halaman mana pun; tombol berubah jadi "Tersalin" selama 2 detik
    document.addEventListener('click', async (event) => {
        const btn = event.target.closest('[data-copy]');
        if (!btn) return;
        event.preventDefault();
        const ok = await copyText(btn.dataset.copy);
        showToast(ok ? '✓ Berhasil disalin' : '✕ Gagal menyalin. Tekan lama teksnya untuk menyalin manual.', ok ? 'success' : 'error');
        if (!ok) return;
        btn.classList.add('is-copied');
        clearTimeout(btn.copiedTimer);
        btn.copiedTimer = setTimeout(() => btn.classList.remove('is-copied'), 2000);
    });

    document.addEventListener('DOMContentLoaded', () => {
        @if (session('toast'))
            showToast(@js(session('toast')[1]), @js(session('toast')[0]));
        @elseif ($errors->any())
            showToast('⚠ Periksa kembali isian form Anda.', 'warning');
        @endif
    });
</script>
