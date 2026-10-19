{{-- Tombol salin; logika klik ada di partials/toast (global untuk semua [data-copy]) --}}
@props(['value', 'label' => 'Salin', 'name' => null])

<button type="button" data-copy="{{ $value }}" aria-label="Salin {{ $name ?? $label }}"
    {{ $attributes->merge(['class' => 'group inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-border bg-surface px-2.5 py-1.5 text-xs font-semibold text-primary transition hover:border-primary hover:bg-primary-light [&.is-copied]:border-success [&.is-copied]:bg-green-50 [&.is-copied]:text-success']) }}>
    <x-icon name="copy" class="h-4 w-4 group-[.is-copied]:hidden" />
    <x-icon name="check" class="hidden h-4 w-4 group-[.is-copied]:block" />
    <span class="group-[.is-copied]:hidden">{{ $label }}</span>
    <span class="hidden group-[.is-copied]:inline">Tersalin</span>
</button>
