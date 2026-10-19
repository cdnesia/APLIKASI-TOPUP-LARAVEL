@props(['title', 'sections'])

<x-layouts.app :title="$title">
    <x-page-header :title="$title" :subtitle="'Terakhir diperbarui: '.now()->translatedFormat('d F Y')" />
    <article class="mx-auto max-w-3xl space-y-8 px-4 pt-10 leading-relaxed">
        <p class="text-text-muted">{{ $slot }}</p>
        @foreach ($sections as $i => [$heading, $points])
            <section>
                <h2 class="text-lg sm:text-xl">{{ $i + 1 }}. {{ $heading }}</h2>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-text-muted">
                    @foreach ($points as $point)
                        <li>{{ $point }}</li>
                    @endforeach
                </ul>
            </section>
        @endforeach
        <p class="rounded-lg border border-border bg-surface p-4 text-text-muted">
            Pertanyaan terkait halaman ini dapat disampaikan melalui <a href="{{ route('contact') }}" class="font-medium text-primary hover:underline">halaman Kontak</a>.
        </p>
    </article>
</x-layouts.app>
