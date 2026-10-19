<div class="space-y-2">
    @foreach ($faqs as [$question, $answer])
        <details class="group rounded-2xl border border-border bg-surface px-5 transition open:shadow-soft" @if ($loop->first) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 font-semibold">
                {{ $question }}
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-bg text-text-muted transition group-open:rotate-45">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                </span>
            </summary>
            <p class="pb-5 leading-relaxed text-text-muted">{{ $answer }}</p>
        </details>
    @endforeach
</div>
