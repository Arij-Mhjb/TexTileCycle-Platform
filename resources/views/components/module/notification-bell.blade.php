@props(['dark' => false])
@php
    $user = auth()->user();
    $items = $user ? \App\Services\NotificationCenter::queryFor($user)->latest('id')->limit(8)->get() : collect();
    $unread = $user ? \App\Services\NotificationCenter::queryFor($user)->whereNull('read_at')->count() : 0;
@endphp
@if($user)
<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button type="button" @click="open = !open" :aria-expanded="open" aria-label="Notifications{{ $unread ? ' ('.$unread.' non lues)' : '' }}"
        @class([
            'relative grid h-11 w-11 shrink-0 place-items-center rounded-2xl border transition',
            'border-white/15 bg-white/5 text-white/80 hover:bg-white/10 hover:text-white' => $dark,
            'border-slate-200 bg-white text-slate-600 shadow-sm hover:border-emerald-300 hover:text-emerald-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' => ! $dark,
        ])>
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/></svg>
        @if($unread)
            <span class="absolute -right-1 -top-1 grid h-5 min-w-[1.25rem] place-items-center rounded-full bg-rose-500 px-1 text-[11px] font-bold leading-none text-white ring-2 {{ $dark ? 'ring-[#1d0b24]' : 'ring-white dark:ring-slate-950' }}">{{ $unread > 9 ? '9+' : $unread }}</span>
        @endif
    </button>

    <div x-cloak x-show="open" x-transition @click.outside="open = false"
        class="absolute right-0 z-50 mt-2 w-80 sm:w-96 overflow-hidden rounded-2xl border border-slate-200 bg-white text-left text-slate-800 shadow-2xl dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
            <p class="text-sm font-bold">Notifications @if($unread)<span class="ml-1 rounded-full bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-600 dark:bg-rose-950/50">{{ $unread }} nouvelle{{ $unread > 1 ? 's' : '' }}</span>@endif</p>
            @if($unread)
                <form method="POST" action="{{ route('notification-center.read-all') }}">@csrf<button class="text-xs font-semibold text-slate-500 hover:text-slate-900 dark:hover:text-white">Tout marquer comme lu</button></form>
            @endif
        </div>
        <div class="max-h-96 divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
            @forelse($items as $notification)
                <form method="POST" action="{{ route('notification-center.open', $notification->id) }}">
                    @csrf
                    <button class="flex w-full gap-3 px-4 py-3 text-left transition hover:bg-slate-50 dark:hover:bg-slate-800/60 {{ $notification->read_at ? 'opacity-60' : '' }}">
                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-slate-300 dark:bg-slate-600' : 'bg-rose-500' }}"></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold leading-snug">{{ $notification->title }}</span>
                            <span class="mt-0.5 block text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ \Illuminate\Support\Str::limit($notification->message, 140) }}</span>
                            <span class="mt-1 block text-[11px] text-slate-400">{{ $notification->created_at?->diffForHumans() }}</span>
                        </span>
                    </button>
                </form>
            @empty
                <p class="px-4 py-8 text-center text-sm text-slate-500">Aucune notification pour le moment.</p>
            @endforelse
        </div>
    </div>
</div>
@endif
