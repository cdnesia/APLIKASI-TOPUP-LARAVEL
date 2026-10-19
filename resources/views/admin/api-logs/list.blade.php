{{-- Daftar log API: dipakai di halaman Log API dan detail transaksi --}}
<ul class="divide-y divide-border">
    @forelse ($logs as $log)
        <li>
            <details class="group">
                <summary class="flex cursor-pointer list-none flex-wrap items-center gap-x-3 gap-y-1 px-4 py-3 hover:bg-bg sm:flex-nowrap">
                    <span @class(['h-2 w-2 shrink-0 rounded-full', 'bg-success' => $log->success, 'bg-danger' => ! $log->success]) aria-hidden="true"></span>
                    <span class="sr-only">{{ $log->success ? 'Berhasil' : 'Gagal' }}</span>
                    <span class="badge shrink-0 {{ $log->provider === 'duitku' ? 'bg-blue-100 text-blue-800' : 'bg-violet-100 text-violet-800' }}">{{ \App\Models\ApiLog::PROVIDERS[$log->provider] ?? $log->provider }}</span>
                    <span class="shrink-0 text-xs font-semibold text-text-muted" title="{{ $log->direction === 'in' ? 'Callback/webhook masuk' : 'Request keluar' }}">{{ $log->direction === 'in' ? '⇠ MASUK' : '⇢ KELUAR' }}</span>
                    <code class="shrink-0 text-xs">{{ $log->endpoint }}</code>
                    <span @class(['min-w-0 flex-1 truncate text-sm', 'text-danger' => ! $log->success])>{{ $log->message ?? '-' }}</span>
                    <span class="ml-auto shrink-0 text-right text-xs text-text-muted">
                        @if ($log->invoice && ! ($hideInvoice ?? false))
                            <span class="font-medium text-text">{{ $log->invoice }}</span> ·
                        @endif
                        {{ $log->http_status ? "HTTP {$log->http_status} · " : '' }}{{ $log->duration_ms !== null ? "{$log->duration_ms} ms · " : '' }}{{ $log->created_at->format('d/m H:i:s') }}
                    </span>
                </summary>
                <div class="grid gap-3 bg-bg px-4 py-3 lg:grid-cols-2">
                    @foreach (['Request' => $log->request, 'Respons' => $log->response] as $label => $payload)
                        <div class="min-w-0">
                            <p class="mb-1 text-xs font-semibold text-text-muted">{{ $label }}</p>
                            <pre class="max-h-72 overflow-auto rounded-lg border border-border bg-surface p-3 text-[11px] leading-relaxed">{{ $payload ? json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '—' }}</pre>
                        </div>
                    @endforeach
                    @if ($log->ip)
                        <p class="text-xs text-text-muted lg:col-span-2">IP pengirim: {{ $log->ip }}</p>
                    @endif
                </div>
            </details>
        </li>
    @empty
        <li class="px-4 py-12 text-center">
            <p class="font-medium">Belum ada log</p>
            <p class="mt-1 text-xs text-text-muted">Log muncul otomatis setiap ada komunikasi dengan Digiflazz atau Duitku.</p>
        </li>
    @endforelse
</ul>
