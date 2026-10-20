@props(['label', 'active' => false])

<div
    class="relative w-full lg:w-auto"
    x-data="{ open: false }"
    @mouseenter="open = true"
    @mouseleave="open = false"
    @keydown.escape.stop="open = false; $refs.trigger.focus()"
>
    <button
        x-ref="trigger"
        type="button"
        class="flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2 text-left text-sm font-bold transition lg:w-auto {{ $active ? 'bg-emerald-50 text-emerald-800' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-950' }}"
        aria-haspopup="true"
        :aria-expanded="open.toString()"
        @click="open = ! open"
        @focus="if (window.matchMedia('(min-width: 1024px)').matches) open = true"
    >
        <span>{{ $label }}</span>
        <svg class="h-4 w-4 transition-transform" :class="open && 'rotate-180'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        @click.outside="open = false"
        class="mt-1 grid gap-1 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-900/10 lg:absolute lg:left-0 lg:top-full lg:z-50 lg:mt-2 lg:w-72"
        role="menu"
    >
        {{ $slot }}
    </div>
</div>
