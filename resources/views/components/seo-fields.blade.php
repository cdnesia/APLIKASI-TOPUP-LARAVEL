@props([
    'titleName', 'descName', 'titleValue' => null, 'descValue' => null,
    'titlePlaceholder' => '', 'descPlaceholder' => '', 'url' => url('/'), 'suffix' => null, 'hint' => null,
    'example' => [], // contoh nilai variabel template, mis. ['{nama}' => 'Mobile Legends']
])
{{-- Field judul & deskripsi SEO dengan penghitung karakter dan pratinjau hasil Google --}}
<div class="space-y-4" data-seo-fields data-suffix="{{ $suffix }}" data-example='@json($example)'>
    <div>
        <div class="flex items-baseline justify-between gap-2">
            <label for="{{ $titleName }}" class="label">Judul di Google (meta title)</label>
            <span class="text-xs text-text-muted" data-count="title"></span>
        </div>
        <input id="{{ $titleName }}" name="{{ $titleName }}" value="{{ $titleValue }}" maxlength="120" placeholder="{{ $titlePlaceholder }}"
            class="input @error($titleName) input-error @enderror" data-seo="title">
        @error($titleName) <p class="field-error">{{ $message }}</p> @enderror
    </div>
    <div>
        <div class="flex items-baseline justify-between gap-2">
            <label for="{{ $descName }}" class="label">Deskripsi di Google (meta description)</label>
            <span class="text-xs text-text-muted" data-count="desc"></span>
        </div>
        <textarea id="{{ $descName }}" name="{{ $descName }}" rows="3" maxlength="300" placeholder="{{ $descPlaceholder }}"
            class="input @error($descName) input-error @enderror" data-seo="desc">{{ $descValue }}</textarea>
        @error($descName) <p class="field-error">{{ $message }}</p> @else @if ($hint) <p class="hint">{!! $hint !!}</p> @endif @enderror
    </div>

    <div class="rounded-xl border border-border bg-surface p-4">
        <p class="mb-2 text-[11px] font-semibold tracking-wider text-text-muted uppercase">Pratinjau di Google{{ $example ? ' · contoh: '.($example['{nama}'] ?? '') : '' }}</p>
        <p class="truncate text-xs text-text-muted">{{ preg_replace('#^https?://#', '', $url) }}</p>
        <p class="mt-0.5 truncate text-lg leading-snug text-[#1a0dab] dark:text-[#8ab4f8]" data-preview="title"></p>
        <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-[#4d5156] dark:text-[#bdc1c6]" data-preview="desc"></p>
    </div>
</div>

@once
    @push('scripts')
        <script>
            // Penghitung karakter + pratinjau Google; nilai kosong memakai placeholder (nilai otomatis)
            document.querySelectorAll('[data-seo-fields]').forEach((box) => {
                const limits = { title: 60, desc: 160 };
                const example = JSON.parse(box.dataset.example || '{}');
                const fill = (text) => Object.entries(example).reduce((t, [k, v]) => t.replaceAll(k, v), text);
                const update = () => ['title', 'desc'].forEach((key) => {
                    const field = box.querySelector(`[data-seo="${key}"]`);
                    let text = fill(field.value.trim() || field.placeholder);
                    if (key === 'title' && box.dataset.suffix) text += ` · ${box.dataset.suffix}`;
                    const len = field.value.trim().length;
                    const counter = box.querySelector(`[data-count="${key}"]`);
                    counter.textContent = len ? `${len}/${limits[key]} karakter` : 'Otomatis';
                    counter.classList.toggle('text-warning', len > limits[key]);
                    box.querySelector(`[data-preview="${key}"]`).textContent = text.length > limits[key] + 5 ? text.slice(0, limits[key] + 2) + '…' : text;
                });
                box.addEventListener('input', update);
                update();
            });
        </script>
    @endpush
@endonce
