<x-layouts.admin title="Pengaturan">
    <div class="grid gap-6 lg:grid-cols-3">
        <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6 lg:col-span-2">
            @csrf @method('PUT')

            <section class="card space-y-4 p-5 sm:p-6">
                <h3>Toko</h3>
                <div>
                    <label for="site_name" class="label">Nama Toko</label>
                    <input id="site_name" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required class="input @error('site_name') input-error @enderror">
                    @error('site_name') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="site_tagline" class="label">Tagline</label>
                    <input id="site_tagline" name="site_tagline" value="{{ old('site_tagline', $settings['site_tagline']) }}" class="input">
                </div>
                <div>
                    <label for="whatsapp_admin" class="label">WhatsApp CS</label>
                    <input id="whatsapp_admin" name="whatsapp_admin" value="{{ old('whatsapp_admin', $settings['whatsapp_admin']) }}" class="input @error('whatsapp_admin') input-error @enderror" placeholder="6281234567890">
                    @error('whatsapp_admin') <p class="field-error">{{ $message }}</p> @else <p class="hint">Format 62xxx. Kosongkan untuk menyembunyikan tombol CS.</p> @enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="contact_email" class="label">Email Kontak</label>
                        <input id="contact_email" name="contact_email" type="email" value="{{ old('contact_email', $settings['contact_email']) }}" class="input @error('contact_email') input-error @enderror" placeholder="cs@domainanda.com">
                        @error('contact_email') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="instagram" class="label">Instagram</label>
                        <input id="instagram" name="instagram" value="{{ old('instagram', $settings['instagram']) }}" class="input @error('instagram') input-error @enderror" placeholder="username tanpa @">
                        @error('instagram') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label for="operational_hours" class="label">Jam Operasional Admin</label>
                    <input id="operational_hours" name="operational_hours" value="{{ old('operational_hours', $settings['operational_hours']) }}" class="input" placeholder="Setiap hari, 08.00–22.00 WIB">
                    <p class="hint">Tampil di halaman Kontak dan footer. Penting karena konfirmasi pembayaran masih manual.</p>
                </div>
                <div>
                    <label for="contact_address" class="label">Alamat Usaha</label>
                    <textarea id="contact_address" name="contact_address" rows="2" class="input">{{ old('contact_address', $settings['contact_address']) }}</textarea>
                </div>
                <div>
                    <label for="announcement" class="label">Pengumuman (tampil di atas website)</label>
                    <input id="announcement" name="announcement" value="{{ old('announcement', $settings['announcement']) }}" class="input" placeholder="Mis. Konfirmasi pembayaran pukul 08.00–22.00 WIB">
                </div>
            </section>

            <section class="card space-y-4 p-5 sm:p-6">
                <h3>Harga & Pesanan</h3>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="margin_percent" class="label">Margin (%)</label>
                        <input id="margin_percent" name="margin_percent" type="number" step="0.1" min="0" value="{{ old('margin_percent', $settings['margin_percent']) }}" required class="input @error('margin_percent') input-error @enderror">
                        @error('margin_percent') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="margin_flat" class="label">Margin Tetap (Rp)</label>
                        <input id="margin_flat" name="margin_flat" type="number" min="0" value="{{ old('margin_flat', $settings['margin_flat']) }}" required class="input @error('margin_flat') input-error @enderror">
                        @error('margin_flat') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="price_rounding" class="label">Pembulatan (Rp)</label>
                        <input id="price_rounding" name="price_rounding" type="number" min="1" value="{{ old('price_rounding', $settings['price_rounding']) }}" required class="input @error('price_rounding') input-error @enderror">
                        @error('price_rounding') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="hint">Contoh: modal Rp10.000, margin 5% + Rp0, pembulatan 100 → harga jual Rp10.500. Setelah mengubah margin, klik "Hitung Ulang Harga" di halaman Produk.</p>
                <div class="sm:w-1/3">
                    <label for="order_expiry_hours" class="label">Batas Waktu Bayar (jam)</label>
                    <input id="order_expiry_hours" name="order_expiry_hours" type="number" min="1" max="72" value="{{ old('order_expiry_hours', $settings['order_expiry_hours']) }}" required class="input @error('order_expiry_hours') input-error @enderror">
                    @error('order_expiry_hours') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </section>

            <button class="btn-primary">Simpan Pengaturan</button>
        </form>

        <aside class="card h-fit space-y-4 p-5 sm:p-6">
            <h3>Koneksi Digiflazz</h3>
            <p class="text-text-muted">Kredensial disimpan di file <code>.env</code> server, bukan di database:</p>
            <pre class="overflow-x-auto rounded-lg bg-slate-900 p-3 text-xs text-slate-100">DIGIFLAZZ_USERNAME=...
DIGIFLAZZ_KEY=...
DIGIFLAZZ_TESTING=true
DIGIFLAZZ_WEBHOOK_SECRET=...</pre>
            <div>
                <p class="label">URL Webhook</p>
                <code class="block rounded-lg bg-slate-100 p-2 text-xs break-all">{{ $webhookUrl }}</code>
                <p class="hint">Daftarkan URL ini di Digiflazz → Atur Koneksi → API → Webhook, dengan secret yang sama seperti DIGIFLAZZ_WEBHOOK_SECRET.</p>
            </div>
        </aside>
    </div>
</x-layouts.admin>
