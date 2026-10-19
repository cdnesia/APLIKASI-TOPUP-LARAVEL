<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <meta name="robots" content="noindex">
    <title>Masuk Admin — {{ \App\Models\Setting::get('site_name') }}</title>
</head>
<body class="flex min-h-screen items-center justify-center p-4 font-sans">
    <main class="w-full max-w-sm">
        <div class="mb-6 text-center">
            <x-logo size="lg" />
            <h1 class="mt-6 text-2xl">Masuk ke panel admin</h1>
            <p class="mt-1 text-text-muted">Kelola pesanan, produk, dan pembayaran.</p>
        </div>
        <form action="{{ route('login') }}" method="POST" class="card space-y-4 p-6">
            @csrf
            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="input @error('email') input-error @enderror">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="label">Password</label>
                <input id="password" name="password" type="password" required class="input">
            </div>
            <label class="flex items-center gap-2 text-sm text-text-muted">
                <input type="checkbox" name="remember" class="rounded border-border-dark text-primary"> Ingat saya
            </label>
            <button class="btn-primary w-full">Masuk</button>
        </form>
        <p class="mt-4 text-center text-xs"><a href="{{ route('home') }}" class="text-text-muted hover:text-primary">← Kembali ke website</a></p>
    </main>
    @include('partials.toast')
</body>
</html>
