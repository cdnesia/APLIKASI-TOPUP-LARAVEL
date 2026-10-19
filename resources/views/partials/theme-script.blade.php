{{-- Terapkan tema sebelum halaman dirender agar tidak berkedip --}}
<script>
    (() => {
        try {
            const saved = localStorage.getItem('theme');
            const dark = saved ? saved === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', dark);
        } catch (e) {}
    })();
    function toggleTheme() {
        const dark = document.documentElement.classList.toggle('dark');
        try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
        document.querySelector('meta[name=theme-color]')?.setAttribute('content', dark ? '#0a0d16' : '#f7f8fb');
    }
</script>
