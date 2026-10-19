@php
    use App\Models\Setting;
    $wa = Setting::get('whatsapp_admin');
    $email = Setting::get('contact_email');
    $ig = Setting::get('instagram');
    $address = Setting::get('contact_address');
@endphp
<x-layouts.app title="Kontak">
    <x-page-header eyebrow="Kontak" title="Hubungi Kami" subtitle="Ada kendala pesanan atau pertanyaan? Sertakan nomor invoice agar kami bisa membantu lebih cepat." />

    <div class="mx-auto grid max-w-6xl gap-6 px-4 pt-10 lg:grid-cols-3">
        <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
            @if ($wa)
                <a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="card flex gap-4 p-5 transition hover:border-primary">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-green-100 text-green-700"><x-icon name="phone" /></span>
                    <div>
                        <h3 class="text-base">WhatsApp CS</h3>
                        <p class="mt-1 text-text-muted">+{{ $wa }}</p>
                        <p class="mt-2 text-xs font-semibold text-primary">Chat sekarang →</p>
                    </div>
                </a>
            @endif
            @if ($email)
                <a href="mailto:{{ $email }}" class="card flex gap-4 p-5 transition hover:border-primary">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-primary-light text-primary"><x-icon name="mail" /></span>
                    <div>
                        <h3 class="text-base">Email</h3>
                        <p class="mt-1 break-all text-text-muted">{{ $email }}</p>
                    </div>
                </a>
            @endif
            @if ($ig)
                <a href="https://instagram.com/{{ $ig }}" target="_blank" rel="noopener" class="card flex gap-4 p-5 transition hover:border-primary">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-accent-light text-accent-dark"><x-icon name="star" /></span>
                    <div>
                        <h3 class="text-base">Instagram</h3>
                        <p class="mt-1 text-text-muted">{{ '@'.$ig }}</p>
                    </div>
                </a>
            @endif
            @if ($address)
                <div class="card flex gap-4 p-5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-text-muted"><x-icon name="map" /></span>
                    <div>
                        <h3 class="text-base">Alamat</h3>
                        <p class="mt-1 whitespace-pre-line text-text-muted">{{ $address }}</p>
                    </div>
                </div>
            @endif
            @if (! $wa && ! $email && ! $ig && ! $address)
                <div class="card p-6 sm:col-span-2">
                    <p class="font-medium">Informasi kontak sedang diperbarui.</p>
                    <p class="mt-1 text-text-muted">Sementara itu, Anda dapat mengecek status pesanan melalui menu Cek Transaksi.</p>
                    <a href="{{ route('track') }}" class="btn-primary mt-4">Cek Transaksi</a>
                </div>
            @endif
        </div>

        <aside class="card h-fit p-6">
            <h3 class="flex items-center gap-2 text-base"><x-icon name="clock" class="h-5 w-5 text-primary" /> Jam Operasional</h3>
            <p class="mt-2 text-text-muted">{{ Setting::get('operational_hours') }}</p>
            <div class="mt-5 border-t border-border pt-5">
                <h3 class="text-base">Sebelum menghubungi CS</h3>
                <ul class="mt-2 space-y-2 text-text-muted">
                    <li>• Siapkan nomor invoice pesanan</li>
                    <li>• Cek status di menu <a href="{{ route('track') }}" class="text-primary hover:underline">Cek Transaksi</a></li>
                    <li>• Baca <a href="{{ route('guide') }}#faq" class="text-primary hover:underline">FAQ</a> untuk jawaban cepat</li>
                </ul>
            </div>
        </aside>
    </div>
</x-layouts.app>
