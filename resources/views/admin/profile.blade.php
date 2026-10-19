<x-layouts.admin title="Profil Saya" subtitle="Kelola data login admin">
    <div class="grid max-w-4xl gap-6 lg:grid-cols-2">
        <form action="{{ route('admin.profile.update') }}" method="POST" class="card space-y-4 p-5 sm:p-6">
            @csrf @method('PUT')
            <h3 class="text-base">Informasi Akun</h3>
            <div>
                <label for="name" class="label">Nama</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required class="input @error('name') input-error @enderror">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email" class="label">Email Login</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="input @error('email') input-error @enderror">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <button class="btn-primary">Simpan Perubahan</button>
        </form>

        <form action="{{ route('admin.profile.password') }}" method="POST" class="card space-y-4 p-5 sm:p-6">
            @csrf @method('PUT')
            <h3 class="text-base">Ganti Password</h3>
            <div>
                <label for="current_password" class="label">Password Saat Ini</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="input @error('current_password') input-error @enderror">
                @error('current_password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="label">Password Baru</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="input @error('password') input-error @enderror">
                @error('password') <p class="field-error">{{ $message }}</p> @else <p class="hint">Minimal 8 karakter, kombinasi huruf dan angka.</p> @enderror
            </div>
            <div>
                <label for="password_confirmation" class="label">Ulangi Password Baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
            </div>
            <button class="btn-primary">Ganti Password</button>
        </form>
    </div>
</x-layouts.admin>
