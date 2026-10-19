{{-- Toast: pojok kanan bawah, hilang otomatis setelah 3 detik --}}
<div id="toast-container" class="fixed right-4 bottom-20 z-50 md:bottom-5 flex flex-col gap-2 sm:right-5" aria-live="polite"></div>

<script>
    function showToast(message, type = 'success') {
        const colors = { success: 'bg-green-600', error: 'bg-red-600', warning: 'bg-yellow-500', info: 'bg-blue-600' };
        const toast = document.createElement('div');
        toast.className = `${colors[type] ?? colors.info} text-white text-sm font-medium px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 min-w-[260px] max-w-sm transition-all duration-300 translate-x-full opacity-0`;
        toast.textContent = message;
        document.getElementById('toast-container').appendChild(toast);
        requestAnimationFrame(() => toast.classList.remove('translate-x-full', 'opacity-0'));
        setTimeout(() => {
            toast.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    document.addEventListener('DOMContentLoaded', () => {
        @if (session('toast'))
            showToast(@js(session('toast')[1]), @js(session('toast')[0]));
        @elseif ($errors->any())
            showToast('⚠ Periksa kembali isian form Anda.', 'warning');
        @endif
    });
</script>
